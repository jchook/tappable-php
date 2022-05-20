set positional-arguments

default:
  just --list

coverage *args="":
  bin/coverage "$@"

readme:
  grip

serve:
  npx http-server

tests *args="":
  bin/tests "$@"

watch *args="":
  bin/watch "$@"
