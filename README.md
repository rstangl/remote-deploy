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

I use remote-deploy to upload web-app projects to a shared web-host which provide only FTP access.


Compatibility
=============

Remote-deploy requires PHP 5.
It currently only supports plain FTP (no SFTP, FTPS or FTPES).


Getting Started
===============

TODO


Alternatives
============

If you use git then git-ftp (https://github.com/git-ftp) may be the better choice.
It does not need to build any checksums, it just uploads a file containing the current commit ID. Additionally it
already supports SFTP, FTPS and FTPES.


Planned Features
================

- "catchup" feature (as implemented in git-ftp)
- maybe SFTP, FTPS, FTPES support
