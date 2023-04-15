<?php
/**
 * Simple updates of already generated book from Wikisource.
 * 
 * For now assumes one main HTML file.
 */
// lib
require_once './EpubGenerator.php';

// setup
$basePath = '../Sylwandira_calosc_test/OPS/';
$mainHtml = 'c0_Sylwandira_ca_o__.xhtml';
$url = 'https://pl.wikisource.org/wiki/Sylwandira/ca%C5%82o%C5%9B%C4%87?action=render';

// init
$gen = new EpubGenerator($basePath);

// download first and save as cache?
// could later check last change and update raw html only when needed

// update
$gen->update($url, $mainHtml);