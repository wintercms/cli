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
  help             Displays help for a command
  list             Lists commands
  self-update      [selfupdate] Self-updates the October CLI helper.
 github
  github:token     Sets the GitHub Access token for the helper.
 install
  install:check    Checks the current environment that it can run October CMS.
 project
  project:alerts   View security alerts for an October CMS project.
  project:version  Determines the version of October CMS in use in a project.
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

## Other Requirements

Some commands (such as the `project:alerts` command) use the GitHub API, and may not be accessible due to rate limits imposed by GitHub. To work around this, you can create an Access Token in your GitHub account and store this with the CLI helper for future use.

You can create the necessary token by going to the following URL:
https://github.com/settings/tokens/new?scopes=public_repo&description=October%20CLI%20Helper

Once done, you can then use the `october github:token` command, adding the token as an argument, to store the token and allow the CLI helper to use your token for future API calls.

## Usage

Run the tool by running `october` (or `october.phar`). By default, it will show the help screen with command line options and available commands

## Commands

### `self-update`

```
october self-update
october selfupdate
```

This will update the October CLI helper to the latest version, if not already installed.

### `github:token`

```
october github:token [token]
```

Registers a GitHub Access Token which is used for some commands that use the GitHub API and may be subject to rate limits from GitHub (such as the `project:alerts` command). This will be stored in the user's home directory for future use.

### `install:check`

```
october install:check
```

This command allows you to check that your current environment can run October CMS. 

The command checks that your PHP version is compatible, ensures that the necessary extensions are installed and that configuration settings for PHP are correctly set. If any requirements are not met, the command will give you suggestions on the necessary steps to take in order to make your environment compatible for October CMS.

### `project:alerts`

```
october project:alerts [path]
```

This will compare the installed version of October CMS against the [database of security advisories](https://github.com/octobercms/october/security/advisories) published by the October CMS maintainers, and will indicate whether your October CMS instance needs to be updated or not.

### `project:version`

```
october project:version [-d|--detailed] [path]
```

This will detect the installed version of October CMS in the given path. The path is optional - if not provided, it will look in the current work directory.

The optional `-d|--detailed` option will also print out a list of modified, created or deleted files in the October CMS installation, if any changes have been made to the core October CMS files.
