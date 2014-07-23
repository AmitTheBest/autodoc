# Your Agile Toolkit Project

Welcome to your new Agile Toolkit project. Modify this file to contain more information about your project.

## Install

Generic help on installing Agile-Toolkit based projects can be found

 * http://agiletoolkit.org/doc/install/project


```
rm -rf .git
git init
git add .
git commit -m "Initial Commit"
```

* Install [Composer] [0]

```
$ curl -s https://getcomposer.org/installer | php
```

* Update packages

```
$ php composer.phar update
```

## Set up SQLite folder

Create folder 'data' and make sure it's writable by PHP. It will be used t ostorte SQLite data.
