:: Delete old data
del brmediathek.host

:: get recent version of the provider base class
copy /Y ..\provider-boilerplate\src\provider.php provider.php

:: create the .tar.gz
7z a -ttar -so brmediathek INFO brmediathek.php provider.php | 7z a -si -tgzip brmediathek.host

del provider.php