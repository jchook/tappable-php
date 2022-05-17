set positional-arguments

default:
  just --list

coverage *args="":
  bin/coverage "$@"

tests *args="":
  bin/tests "$@"

watch *args="":
  bin/watch "$@"
