#!/bin/bash

echo -e "\e[32mUninstalling\e[39m"

read -p "Do you confirm you want to uninstall this stack (yes/no)? " ANSWER

if [[ ${ANSWER} != "yes" ]]
then
    echo -e ""
    echo -e "\e[33mUninstall aborted\e[39m"
    exit 1
fi

echo -e ""
echo -e "\e[32mStoppping services\e[39m\n"
docker compose stop

echo -e ""
echo -e "\e[32mRemoving containers and volumes\e[39m\n"
docker compose down --volumes

echo -e ""
echo -e "\e[32mDone\e[39m"