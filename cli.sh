#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
phpVersion=8.2
containerName="phespro-container-php-$phpVersion"

docker build "$DIR" -t "$containerName" --build-arg "PHP_VERSION=$phpVersion"
docker run -it  -v "$DIR:/code" -w "/code" "$containerName" bash