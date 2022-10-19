#!/usr/bin/env bash

# ensure mount point /app bound to host repo directory using docker --mount argument
if [[ ! -d /data ]]; then
  echo "mount point '/data' is not bound to a host directory. Run 'docker ... --mount type=bind,source=<host-repo-directory>,target=/data ...'"
  exit 1
fi 

if [[ $# -eq 0 ]]; then
  echo "show help"
  exit 0
fi

if [[ $# -eq 1 ]]; then
  if [[ "$1" = "init" ]]; then
    # ensure book.toml exists
    if [[ ! -f /data/book.toml ]]; then
      echo "'/data/book.toml' does not exist - will generate it"
      echo 'n' | mdbook init --title="" 

      echo "initializing mdbook plugin mdbook-toc"
      printf '\n[preprocessor.toc]\ncommand = "mdbook-toc"\n' >> /data/book.toml
    
      echo "initializing mdbook plugin mdbook-mermaid"
      mdbook-mermaid install |:

      exit 0
    else 
      echo "'/data/book.toml' already exists. Remove it and execute 'init' again"
      exit 1
    fi
  fi
fi

# add a entrypoint script to enable CTRL-C abortion in terminal
# (see https://stackoverflow.com/a/57526365/1554103)
$@