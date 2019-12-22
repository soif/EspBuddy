# Windows Installation

#### WINDOWS SUPPORT IS EXPERIMENTAL !!!

I've done my best to support Windows, in most part of EspBuddy, but I've not fully tested it. **If you want to get a painless Espbuddy usage, please use either OSX (fully tested) or Linux (mostly tested).**


## DISCLAIMER

_This information is based on what I could remember to run EspBuddy on Windows. These are only notes, for myself and for your convenience._

* _PLEASE Don't except any Windows support from me!_

* _Do your own tests, and please enhance this documentation and/or push PR to fixes any Windows issues._

* _All tests have been performed on a **Windows 10, 64bits** OS_

----

## Install PHP

_Courtenesy of [this blog article](https://www.jeffgeerling.com/blog/2018/installing-php-7-and-composer-on-windows-10)._


### Install PHP required librry

* Download the LATEST **Visual C++ Redistributable for Visual Studio**, from [here](https://support.microsoft.com/en-us/help/2977003/the-latest-supported-visual-c-downloads). _( I've tested : `x64: vc_redist.x64.exe` )_

* launch the installer, and accept default settings


### Install PHP main binaries

* Download **php7** from [here](https://windows.php.net/download/). _( I've tested :_ `VC15 x64 Non Thread Safe` )_

* Expand the zip file into the path `C:\php7`

* Configure PHP to run correctly on your system:
  * In the `C:\php7` folder, rename the file *php.ini-development* to *php.ini*.
  * Edit the **php.ini** file in a text editor (e.g. Notepad++, Atom, or Sublime Text).
Change the following settings in the file and save the file:

    * Uncomment the line that reads `;extension_dir = "ext"` (remove the `;` so the line is just `extension_dir = "ext"`).

    * In the section where there are a bunch of extension= lines, uncomment the following lines:

      ```php
      extension=php_curl.dll
      extension=php_openssl.dll
      ```

* Add `C:\php7` to your Windows system PATH:

  * Open the Windows Setting / System Control Panel.
  * Search for 'Advanced System Settings'.
  * Click the 'Environment Variables' button.
  * Find the Path row under 'System variables', and click 'Edit'
  * Click 'New' and add the row `C:\php7`.
  * Click OK, then OK, then OK, and close out of the Windows Setting / System Control Panel .

* Launch a console to ensure that php is working, typing: `php -v`

* (TODO) Add EspBuddy in your PATH
  * _TODO: Explain how to directly add EspBuddy.php to the PATH as a PHP executable application, to avaoid having to add 'php' before espbuddy.php ._

     __Willing to help? Please edit this page!__


## Install Python

* Download [python v3.7](https://www.python.org/ftp/python/3.7.0/python-3.7.0-amd64.exe). _I've tested: `Windows x86-64 executable installer`_
* Launch the installer
* Click on _"add Python 3.7 to PATH"_
* Click on _"Install Now"_
* Launch a console to sensure that python is working, typing: `python --version`
* **('sonodiy' command only)** Install required pyton modules from the console :  `pip install zeroconf PySide2`


## Install git

* Download Git for windows from [here](https://gitforwindows.org/). _( I've tested : `Git-2.24.1.2-64-bit.exe` )_
* Launch the installer an accept default options


## Launch EspBuddy

* From a console, go to your Espbuddy folder and run: `php espbuddy.php`
* Finally start enjoying EspBuddy !




