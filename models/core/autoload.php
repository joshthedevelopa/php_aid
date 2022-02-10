<?php

include_once "config.php";

Config::store(".env");

include_once "database.php";
include_once "validator.php";
include_once "view.php";
include_once "router.php";