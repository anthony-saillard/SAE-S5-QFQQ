  #!/bin/bash

echo -e "\e[32mStarting projet\e[39m\n"

echo -e "\n\e[32mPulling images\e[39m\n"
docker compose pull --ignore-buildable

echo -e "\n\e[32mStarting services\e[39m\n"
docker compose up -d

sleep 1

echo -e "\n\e[32mRunning services\e[39m\n"
docker compose ps
