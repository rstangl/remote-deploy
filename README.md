remote-deploy
=============

Efficiently synchronize a remote FTP directory with a local directory.

Remote-deploy can be used to upload a local directory to a FTP server. It will only upload files and directories
which have changed since the last upload.
This synchronization works only one-way, from your local directory to the remote directory.

To be very efficient remote-deploy generates checksums from your local files, puts them into a file and uploads
it to the remote directory. When you invoke remote-deploy it will first download the checksum file and compare
the checksums with the checksums of your local files.
Changed and new files will be uploaded, new directories will be created, locally removed files and directories
will be removed.

I use remote-deploy to upload web-app projects to a shared web host which provides only FTP access.


Compatibility
=============

Remote-deploy requires PHP 5.
It currently only supports plain FTP (no SFTP, FTPS or FTPES).

Remote-deploy should run on any platform PHP is available for, but I have only tested it on Linux.
Feel free to test on other platforms, fix eventually existing problems, and please tell me if it works, so that
I can update this text :)


Getting Started
===============

Basic Usage
-----------

Remote-deploy is called like this:

```
php /path/to/remote-deploy/deploy.php     \
    -h <remote host>                      \
    -u <username>                         \
    [-p <password>]                       \
    [-l <local path>]                     \
    [-r <path on remote host>]            \
    [-c <checksum file on remote host>]   \
    [-e <exclude pattern file>]
```

- **-h** ***&lt;remote host&gt;***
  The FTP server's IP or hostname.
- **-u** ***&lt;username&gt;***
  Username to log-in to the FTP server.
- **-p** ***&lt;password&gt;***
  Password to log-in to the FTP server.
  When you ommit this you will be prompted for the password.
- **-l** ***&lt;local path&gt;***
  Path to the local directory you want to sync.
  When you ommit this the current working directory will be synchronized.
- **-r** ***&lt;path on remote host&gt;***
  Target path on the FTP server.
  When you ommit this the local directory is uploaded to the after-log-in working directory on the FTP server.
  Maybe also an absolute path works. This strongly depends on the FTP server's configuration.
- **-c** ***&lt;checksum file on remote host&gt;***
  Path to the checksum file which will be up/downloaded to/from the FTP server.
  When you ommit this the checksum file is put to/expected to be *"DEPLOY_CHECKSUMS"* in the after-log-in
  working directory on the FTP server.
- **-e** ***&lt;exclude pattern file&gt;***
  Optionally specify a file which tells remote-deploy which files and directories to ignore.
  This basically works the same way as a *.gitignore* file, except that wildcards are not supported.

Example:
```
php /path/to/remote-deploy/deploy.php     \
    -h ftp.example.com                    \
    -u user01                             \
    -p topsecret                          \
    -l /projects/myapp                    \
    -r webapps/myapp                      \
    -c webapps/myapp-deploy-checksums     \
    -e /projects/myapp/deploy.exclude
```

Using an Exclude Pattern File
-----------------------------

Remote-deploy currently does not support wildcards in exclude pattern files. But when you know how to place the
slashes in such a file you have quite good control over which files and directories are uploaded and which are not.

An example *(deploy.exclude)*:
```
deploy.exclude
.project
.git
.gitignore
tests
cache/
uploads/files/
```
The directory *tests* will not be uploaded while the directories *cache* and *uploads/files* will, but their
sub directories and files will be ignored.

Best Practice - Shell Script
----------------------------

For my projects I always write a small deployment shell script (*deploy.sh*) which uses remote-deploy.
```
#!/bin/sh

if [ "$1" = "prod" ]; then
    remotepath="production/myapp"
    checksumfile="production/myapp-prod-checksums.txt"
elif [ "$1" = "test" ]; then
    remotepath="testing/myapp"
    checksumfile="testing/myapp-test-checksums.txt"
else
    echo "Unknown configuration: $1"
    exit 1
fi

host="ftp.example.com"
user="user01"
localpath="$(dirname $0)"
excludefile="$localpath/deploy.exclude"

password=""
if [ -f "$localpath/deploy.password" ]; then
    password="-p $(cat "$localpath/deploy.password")"
fi

php /path/to/remote-deploy/deploy.php     \
    -h "$host"                            \
    -u "$user"                            \
    -l "$localpath"                       \
    -r "$remotepath"                      \
    -c "$checksumfile"                    \
    -e "$excludefile"                     \
    $password
```

This way I can manage a production and a testing environment, for example. I just have to do a `./deploy.sh prod`
or `./deploy.sh test` to deploy my project.

I don't want to enter the FTP user's password each time so I store it in a file (*deploy.password*) and let the
shell script add it to the command line arguments for remote-deploy. Don't forget to add the password file to your
*deploy.exclude* file! I also ignore the password file in my version control system, so it only exists on my local PC.


Alternatives
============

If you use git then git-ftp (https://github.com/git-ftp) may be the better choice.
It does not need to build any checksums, it just uploads a file containing the current commit ID. Additionally it
already supports SFTP, FTPS and FTPES.


Planned Features
================

- "catchup" feature (as implemented in git-ftp)
- maybe SFTP, FTPS, FTPES support

I also plan to:
- Port remote-deploy to PHP 5.3 namespaces and remove the Java-like "import" function (and use PSR-0 autoloading)
- Remove the "ConsoleUtil" and just use a PSR-3 compatible logger instead
- Change cmdline syntax to specify the target as an URL - maybe it is also easier to implement other protocols by
  directly using PHP streams instead of PHP's ftp functions
