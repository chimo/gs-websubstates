<?php
if (!defined('GNUSOCIAL')) {
    exit(1);
}

class WebsubadminpanelAction extends AdminPanelAction
{
    function title() {
        return 'WebSub States';
    }

    function prepare(array $args=array()) {
        parent::prepare($args);

        $this->page = $this->int('page', 1, null, 1);
        $this->sortBy = $this->trimmed('sort-by', 'uri');
        $this->sortDir = $this->trimmed('sort-dir', 'asc');

        $sortable_columns = ['uri', 'sub_state', 'created'];

        // URL param sanitation: make sure 'sort-by' is one of the
        // columns we're expecting. If not, default sorting by 'id'
        if (!in_array($this->sortBy, $sortable_columns)) {
            $this->sortBy = 'uri';
        }

        // URL param sanitation: make sure 'sort-dir' is either 'asc'
        // or 'desc'. Default to 'asc' if neither.
        if ($this->sortDir !== 'desc') {
            $this->sortDir = 'asc';
        }

        $offset = ($this->page - 1) * 10;
        $limit = 10;
        $this->feedsubs = $this->getWebSubs($offset, $limit);
        $this->overview = $this->getOverview();

        return true;
    }

    function getWebSubs($offset, $limit) {
        $oStatusProfile = new Ostatus_profile();
        $feedsub= new FeedSub();
        $profile = new Profile();
        $avatar = new Avatar();

        $oStatusProfile->selectAs($oStatusProfile, 'ostatus_profile_%s');

        $oStatusProfile->joinAdd(array('feeduri', 'feedsub:uri'));
        $oStatusProfile->selectAs($feedsub, 'feedsub_%s');

        $oStatusProfile->joinAdd(array('profile_id', 'profile:id'));
        $oStatusProfile->selectAs($profile, 'profile_%s');

        $oStatusProfile->joinAdd(array('profile_id', 'avatar:profile_id'));
        $oStatusProfile->selectAs($avatar, 'avatar_%s');

        $oStatusProfile->whereAdd('avatar.original = 0');
        $oStatusProfile->whereAdd('avatar.width = 48');
        $oStatusProfile->orderBy('feedsub.' . $this->sortBy . ' ' . $this->sortDir);
        $oStatusProfile->limit($offset, $limit);

        return $oStatusProfile->fetchAll();
    }

    function getOverview() {
        $feedsubs = new FeedSub();
        $feedsubs->selectAdd();
        $feedsubs->selectAdd('count(*) as nb_feedsubs');

        // TODO: handle cases where this fails for wtv reason
        if ($feedsubs->find()) {
            $feedsubs->fetch();
        }

        return array(
            'nb_feedsubs' => $feedsubs->nb_feedsubs,
        );
    }

    function showOverview() {
        $nb_feedsubs = $this->overview['nb_feedsubs'];

        // TODO: Revise wording
        // TODO: add: # active, # inactive, etc. (?)
        $this->element('p', null, "There are $nb_feedsubs WebSubs on your instance.");
    }

    function showSort($by, $direction) {
        $pluginPath = Plugin::staticPath('WebSubStates', "");
        $klass = 'sort-icon';
        $href = '?sort-by=' . $by . '&sort-dir=' . $direction . '&page=' . $this->page;
        $img_src = $pluginPath . '/images/sort-' . $direction . '.png';

        if ($this->sortBy === $by && $this->sortDir === $direction) {
            $klass .= ' active';
        }

        $this->elementStart('a', array('class' => $klass, 'href' => $href));
        $this->element('img', array('src' => $img_src));
        $this->elementEnd('a');
    }

    function showContent() {
        if ($this->page === 1) {
            $this->showOverview();
        }

        if (count($this->feedsubs) === 0) {
            $this->element('p', null, 'No WebSubs found.'); // TODO: Better msg

            return true;
        }

        $this->elementStart('table', array('class' => 'chr-websubs'));
        $this->elementStart('thead');
        $this->elementStart('tr');

        $this->elementStart('th');
        $this->text('WebSub');
        $this->showSort('uri', 'asc');
        $this->showSort('uri', 'desc');
        $this->elementEnd('th');

        $this->elementStart('th');
        $this->text('Date created');
        $this->showSort('created', 'asc');
        $this->showSort('created', 'desc');
        $this->elementEnd('th');

        $this->elementStart('th');
        $this->text('State');
        $this->showSort('sub_state', 'asc');
        $this->showSort('sub_state', 'desc');
        $this->elementEnd('th');

        $this->elementEnd('tr');
        $this->elementEnd('thead');

        $this->elementStart('tbody');

        foreach($this->feedsubs as $feedsub) {
            $this->elementStart('tr');

            $this->elementStart('td');

            // Profile
            $profile_host = parse_url($feedsub->uri, PHP_URL_HOST);
            $this->element('img', array('class' => 'profile-avatar' ,'src' => '/avatar/' . $feedsub->filename));
            $this->elementStart('div', array('class' => 'profile-details'));
            $this->element('a', array('href' => $feedsub->profileurl), $feedsub->nickname . '@' . $profile_host);

            // Feed
            $this->text('(');
            $this->element('a', array('href' => $feedsub->uri), 'feed');
            $this->text(')');
            $this->elementEnd('div');

            $this->elementEnd('td');

            // Date created
            if ($feedsub->ostatus_profile_created) {
                $date_created = date('Y-m-d', strtotime($feedsub->ostatus_profile_created));
            } else {
                // Sometime 'date' is null
                $date_created = '?';
            }

            $this->element('td', null, $date_created);

            // State
            $this->element('td', null, $feedsub->sub_state);

            $this->elementEnd('tr');
        }

        $this->elementEnd('tbody');
        $this->elementEnd('table');

        $this->showPagination($this->page);
    }

    function showPagination($current_page) {
        $have_before = false;
        $have_after = false;

        if ($current_page > 1) {
            $have_before = true;
        }

        // FIXME: This might give us an empty last page if the total
        //        amount of files is a multiple of 10
        if (count($this->feedsubs) === 10) {
            $have_after = true;
        }

        $this->pagination($have_before, $have_after, $current_page, 'websubadminpanel');
    }

    /**
     * This is a copy of Action::pagination because the 'Before'/'After' labels
     * don't make sense and can't be overwritten...
     */
    function pagination($have_before, $have_after, $page, $action, $args=null)
    {
        // Does a little before-after block for next/prev page
        if ($have_before || $have_after) {
            $this->elementStart('ul', array('class' => 'nav',
                                            'id' => 'pagination'));
        }
        if ($have_before) {
            $pargs = array(
                'page' => $page - 1,
                'sort-by' => $this->sortBy,
                'sort-dir' => $this->sortDir
            );
            $this->elementStart('li', array('class' => 'nav_prev'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'prev'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: present than the currently displayed information.
                           _('Previous'));
            $this->elementEnd('li');
        }
        if ($have_after) {
            $pargs   = array(
                'page' => $page + 1,
                'sort-by' => $this->sortBy,
                'sort-dir' => $this->sortDir
            );
            $this->elementStart('li', array('class' => 'nav_next'));
            $this->element('a', array('href' => common_local_url($action, $args, $pargs),
                                      'rel' => 'next'),
                           // TRANS: Pagination message to go to a page displaying information more in the
                           // TRANS: past than the currently displayed information.
                           _('Next'));
            $this->elementEnd('li');
        }
        if ($have_before || $have_after) {
            $this->elementEnd('ul');
        }
    }
}

