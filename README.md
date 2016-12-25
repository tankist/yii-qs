Yii Qs Extensions
=================

Yii Qs Extensions contains basic extensions for [Yii Framework](https://github.com/yiisoft/yii)
developed and used in [QuartSoft](http://quartsoft.com).


INSTALLATION
------------

Generally you may place the content of this repository anywhere.
For the consistency 'protected/extensions/qs' is recommended.
To enable the usage of the extensions to must specify alias 'qs'
in your Yii application configuration to be pointing to 'lib'
directory (protected/extensions/qs.lib), like following:

      // Yii application configuration:
      return array(
          'aliases' => array(
              'qs' => 'ext.qs.lib',
              ...
          ),
          ...
      )


INSTALLING AND UPDATING VIA GIT SUBTREE
---------------------------------------

You can include QS extensions to your project via GIT subtree
mechanism. This will allow you to update extensions when needed,
while keeping the ability to edit them yourself.
To add 'yiiqsextensions' repository to your project perform following
console commands:

      # goto your project directory:
      cd '/my/project/'
      # add remote to 'yiiqsextensions' repository:
      git remote add yiiqsextensions_remote git@git.quart-soft.com:yiiqsextensions
      # fetch 'yiiqsextensions' repository content:
      git fetch yiiqsextensions_remote
      # checkout 'yiiqsextensions' content into separated branch:
      git checkout -b yiiqsextensions_branch yiiqsextensions_remote/master
      # return to your project master branch:
      git checkout master
      # attach 'yiiqsextensions' branch to your project as subtree:
      git read-tree --prefix=protected/extensions/qs/ -u yiiqsextensions_branch
      # add and commit changes:
      git add .
      git commit -m "yiiqsextensions have been added"

For the future update use following console commands:

      # goto your project directory:
      cd '/my/project/'
      # checkout 'yiiqsextensions' branch:
      git checkout yiiqsextensions_branch
      # pull all remote changes:
      git pull yiiqsextensions_remote refs/heads/master:refs/heads/yiiqsextensions_branch
      # return to your project master branch:
      git checkout master
      # merge subtree changes:
      git merge --squash -s subtree --no-commit master
      # add and commit changes:
      git add .
      git commit -m "yiiqsextensions have been updated"

