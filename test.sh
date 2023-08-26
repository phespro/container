#!/usr/bin/env bash

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

docker=`command -v docker`

if [ -z "$docker" ]; then
    printf "\nDocker is missing from your installation.\n"
    exit 1
fi

mutations=NO

for option in "$@"; do
  case $option in
    --mutations)
      mutations=YES
      ;;
  esac
done

phpVersions=( "8.2" )

for version in "${phpVersions[@]}"
do
    docker build "$DIR" -t "phespro-container-php-$version" --build-arg "PHP_VERSION=$version"
    docker run -v "$DIR:/code" -w "/code" "phespro-container-php-$version" composer install --no-interaction --no-progress

    if [[ $mutations == "YES" ]]; then
        docker run -v "$DIR:/code" -w "/code" "phespro-container-php-$version" vendor/bin/infection --min-msi=100 --min-covered-msi=100
    else
        docker run -v "$DIR:/code" -w "/code" "phespro-container-php-$version" vendor/bin/phpunit --coverage-html coverage.html
    fi
done