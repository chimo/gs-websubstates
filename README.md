# Note

I'm done with this. If someone wants to maintain it send me a note and I'll transfer the repo. Or just fork it, or whatever.

# WebSub States Plugin for GNU social

## Installation

1. Navigate to your `/local/plugins` directory (create it if it doesn't exist)
1. `git clone https://github.com/chimo/gs-websubstates.git WebSubStates`

## Configuration

Tell `/config.php` to use it:

```
    addPlugin('WebSubStates');
```

## Usage

A new "WebSub States" link should appear in the left-navigation on the "Admin" page.

On the "WebSub States" page, you should see a list of WebSubs your instance knows about, the date they were created and their status:

![screenshot](https://static.chromic.org/repos/gs-websubstates/screenshot.png)

