<?php
date_default_timezone_set('Europe/Warsaw');
$url = 'https://pl.wikisource.org/w/api.php?action=parse&format=json&page=Sylwandira/ca%C5%82o%C5%9B%C4%87&prop=text|parsewarningshtml|parsewarnings&formatversion=2';
$json = file_get_contents($url);

$json = json_decode($json);
file_put_contents('c0_Sylwandira_ca_o__.xhtml.__EpubGenerator__.raw.html', $json->parse->text);
echo "Done";