#!/bin/bash

echo -e "\e[32mRunning pre-commit checks...\e[39m\n"

./scripts/lint.sh

if [ $? -eq 0 ]; then
    echo -e "\n\e[32mPre-commit checks passed. Proceeding with commit.\e[39m"
    exit 0
else
    echo -e "\n\e[31mPre-commit checks failed. Commit aborted.\e[39m"
    exit 1
fi
