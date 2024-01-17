server http {
    host = "127.0.0.1"
    port = 8000

    proxy albatross {
        from {
            path = "/x/albatross/"
        }
        to {
            host = "127.0.0.1:8000"
            port = 8001
        }
        auth basic {
            realm = "Auth required /x/albatross/"
            credential_file = "albatross.htpasswd"
        }
    }
}
