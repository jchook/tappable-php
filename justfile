set positional-arguments

default:
  just --list

coverage *args="":
  bin/coverage "$@"

readme:
  grip

tests *args="":
  bin/tests "$@"

watch *args="":
  bin/watch "$@"
