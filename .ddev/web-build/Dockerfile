ARG PAGEFIND_VERSION=1.5.2
RUN curl -sL "https://github.com/CloudCannon/pagefind/releases/download/v${PAGEFIND_VERSION}/pagefind-v${PAGEFIND_VERSION}-x86_64-unknown-linux-musl.tar.gz" \
    | tar -xz -C /usr/local/bin pagefind \
    && chmod +x /usr/local/bin/pagefind
