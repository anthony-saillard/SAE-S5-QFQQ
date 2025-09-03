# QFQQ Project

This project enables lessons to be organised according to the national curriculum, 
availability in each week and teachers' requests.

## Work on this project

Requirements:
- git
- docker
- postman (to test routes and add false data)

```shell
mkdir -p ~/sae-projects
cd ~/sae-projects
git clone git@gitlab.iut-valence.fr:saillant/sae-s5-qfqq.git
cd sae-s5-qfqq

# build the image, install packages and start services
./init.sh
```

Default access:
- Frontend: https://localhost
- API: https://localhost/api


## Stop to work on this project

To stop the stack:
```shell
./scripts/stop.sh
```

To uninstall the stack (should delete data):
```shell
./scripts/uninstall.sh
```

## Usefull commands

### Build and UP the app container

To start the stack:
```shell
./scripts/start.sh
```

To build the stack:
```shell
./scripts/build.sh
```

### Run tests

```shell
./scripts/run-tests.sh
```

### Check the linter

```shell
./scripts/lint.sh
```

## Create a new db migration

When a database schema change is required, a new migration should be created:

```shell
# create new migration (if necessary) and apply migrations
./scripts/migrations.sh
```
