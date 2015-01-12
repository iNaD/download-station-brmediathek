:: Delete old data
del brmediathek.host
:: create the .tar.gz
7z a -ttar -so brmediathek INFO brmediathek.php | 7z a -si -tgzip brmediathek.host
