#!/bin/bash

echo -e "\e[32mCreating a new migration\e[39m\n"

docker exec -it api php bin/console make:migration

echo -e "\n\e[32mCreation passed successfully!\e[39m\n"

echo -e "\n\e[33mApplying migrations\e[39m\n"

docker exec -it api php bin/console doctrine:migrations:migrate


