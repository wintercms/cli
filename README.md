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
  help     Displays help for a command
  list     Lists commands
  version  Determines the version of October CMS in use.

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

The command checks for your PHP version and various PHP extensions
