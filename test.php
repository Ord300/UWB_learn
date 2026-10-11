<?php
echo extension_loaded('pdo_mysql') ? 'pdo_mysql OK' : 'pdo_mysql MANQUANT';
echo '<br>';
echo 'Hôte : ' . (getenv('MYSQLHOST') ?: 'variable absente');