user = "ken"

server http {
    host = "0.0.0.0"
    port = 80

    redirect_to_https = true
    acme_challenge {
        root = "letsencrypt/webroot"
    }
}

server https {
    host = "0.0.0.0"
    port = 443

    tls_cert_file = "letsencrypt/lego/certificates/nil.ninja.crt"
    tls_key_file = "letsencrypt/lego/certificates/nil.ninja.key"

    proxy albatross {
        from {
            host = "t.nil.ninja"
            path = "/phperkaigi/2024/golf/"
        }
        to {
            host = "127.0.0.1"
            port = 8001
        }
        auth basic {
            realm = "Auth required albatross"
            credential_file = "albatross.htpasswd"
        }
    }
}
