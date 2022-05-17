set positional-arguments

default:
  just --list

tests *args="":
  vendor/bin/phpunit tests "$@" \
    | sed -E 's/#StandWithUkraine//' \
    | awk -f tests/phpunit.colorize.awk
