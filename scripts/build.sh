#!/bin/bash

echo -e "\e[32mBuild the Docker project\e[39m\n"

# Build Docker images
docker compose build --no-cache

echo -e "\n\e[32mBuild completed successfully!\e[39m"
