#!/usr/bin/env php
<?php

# this is just file for run cli commands by bash

$realPath = realpath(__FILE__);
$projectDir = dirname($realPath);

chdir($projectDir);

require_once $projectDir . '/cli/index.php';
