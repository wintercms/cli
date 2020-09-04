# October CMS CLI Helper

A command line tool to help users manage October CMS installations.

```
Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help           Displays help for a command
  list           Lists commands
  self-update    [selfupdate] Self-updates the October CLI helper.
  version        Determines the version of October CMS in use.
 install
  install:check  Checks if the current environment can run October CMS.

```

## Installation

You can download the latest PHAR release of this tool from the [Releases](https://github.com/bennothommo/october-cli/releases) page. Download this file to your preferred location, then make the PHAR file executable (optionally, renaming it to `october` if you wish):

```
mv october.phar october
chmod a+x october
```

Move this file into one of your `$PATH` directories to make this CLI helper available globally.

```
mv october /usr/bin/october
```

## Usage

Run the tool by running `october` (or `october.phar`). By default, it will show the help screen with command line options and available commands

## Commands

### `self-update`

```
october self-update
october selfupdate
```

This will update the October CLI helper to the latest version, if not already installed.

### `version`

```
october version [-d|--detailed] [path]
```

This will detect the installed version of October CMS in the given path. The path is optional - if not provided, it will look in the current work directory.

The optional `-d|--detailed` option will also print out a list of modified, created or deleted files in the October CMS installation, if any changes have been made to the core October CMS files.

### `install:check`

```
october install:check
```

This command allows you to check that your current environment can run October CMS. 

The command checks that your PHP version is compatible, ensures that the necessary extensions are installed and that configuration settings for PHP are correctly set. If any requirements are not met, the command will give you suggestions on the necessary steps to take in order to make your environment compatible for October CMS.
