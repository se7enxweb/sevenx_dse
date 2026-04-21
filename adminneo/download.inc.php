<?php

namespace AdminNeo;

$TABLE = $_GET["download"];
$fields = fields($TABLE);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . friendly_url("$TABLE-" . implode("_", $_GET["where"])) . "." . friendly_url($_GET["field"]));
$select = [idf_escape($_GET["field"])];
$result = Driver::get()->select($TABLE, $select, [where($_GET, $fields)], $select);
$row = ($result ? $result->fetchRow() : []);
echo Connection::get()->formatValue($row[0], $fields[$_GET["field"]]);
throw new \AdminNeo\EzExit(); // don't output footer
