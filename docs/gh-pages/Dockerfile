FROM rust:latest

# see https://github.com/hrektts/docker-mdbook/blob/master/Dockerfile
ARG MDBOOK_VERSION="0.4.13"
LABEL maintainer="lars.gersmann@cm4all.com" \
  version=$MDBOOK_VERSION

RUN cargo install mdbook --vers ${MDBOOK_VERSION}

WORKDIR /data
VOLUME ["/data"]

# https://fnordig.de/2019/07/11/mdbook-toc-and-mermaid-preprocessors/
RUN cargo install mdbook-toc mdbook-mermaid

# add a entrypoint script to enable CTRL-C abortion in terminal
# (see https://stackoverflow.com/a/57526365/1554103)
RUN echo "#!/bin/bash" > /usr/local/bin/docker-entrypoint.sh && \
  echo '$@' >> /usr/local/bin/docker-entrypoint.sh && \
  chmod a+x /usr/local/bin/docker-entrypoint.sh

LABEL impex_customized="true"

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]