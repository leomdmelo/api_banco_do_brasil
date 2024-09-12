<?php

  require 'v_ambiente/autoload.php';

  $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
  $dotenv->load();

  echo "Variavel de ambiente: " . $_SERVER['TESTE'];

?>