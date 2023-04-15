<?php
/**
 * Simple updates of already generated book from Wikisource.
 * 
 * For now assumes one main HTML file.
 */
// lib
require_once './EpubGenerator.php';
require_once './Logger.php';

// setup
$basePath = '../Sylwandira_calosc_test/OPS/';
$mainHtml = 'c0_Sylwandira_ca_o__.xhtml';
$url = 'https://pl.wikisource.org/wiki/Sylwandira/ca%C5%82o%C5%9B%C4%87?action=render';

// init
$gen = new EpubGenerator($basePath);
$console = new Logger();

// update
$console->log("Updating...");
$gen->update($url, $mainHtml);
$console->log("Update done");

// test
// $html = file_get_contents($basePath.$mainHtml);
// echo $html;
