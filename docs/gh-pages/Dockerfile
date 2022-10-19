FROM rust:latest

# see https://github.com/hrektts/docker-mdbook/blob/master/Dockerfile
ARG MDBOOK_VERSION="0.4.21"
LABEL mdbook_version=$MDBOOK_VERSION

RUN cargo install mdbook --vers ${MDBOOK_VERSION}

# https://fnordig.de/2019/07/11/mdbook-toc-and-mermaid-preprocessors/
RUN cargo install mdbook-toc mdbook-mermaid

COPY ./README.md /
ADD https://raw.githubusercontent.com/rust-lang/mdBook/master/LICENSE /

WORKDIR /data

COPY --chmod=755 ./entrypoint.sh /usr/local/bin/
RUN ls -la /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]