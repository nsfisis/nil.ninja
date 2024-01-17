BIN_ROOT = ENV['BIN_ROOT'] || raise
REPO_ROOT = ENV['REPO_ROOT'] || raise
LEGO_VERSION = ENV['LEGO_VERSION'] || raise
LEGO_ARCH = ENV['LEGO_ARCH'] || raise
LEGO_CONF_EMAIL = ENV['LEGO_CONF_EMAIL'] || raise
LEGO_CONF_WEBROOT = ENV['LEGO_CONF_WEBROOT'] || raise
LEGO_CONF_PATH = ENV['LEGO_CONF_PATH'] || raise
LEGO_CONF_DOMAINS = ENV['LEGO_CONF_DOMAINS'] || raise
GOLANG_VERSION = ENV['GOLANG_VERSION'] || raise
MIOPROXY_VERSION = ENV['MIOPROXY_VERSION'] || raise

lego_tarball = "lego_#{LEGO_VERSION}_linux_#{LEGO_ARCH}.tar.gz"
lego_tarball_url = "https://github.com/go-acme/lego/releases/download/#{LEGO_VERSION}/#{lego_tarball}"
lego_conf_domains = LEGO_CONF_DOMAINS.split(',')
lego_conf_primary_domain = lego_conf_domains.first || raise
lego_run_cmdline = [
  "#{BIN_ROOT}/lego",
  '--accept-tos',
  '--email', LEGO_CONF_EMAIL,
  '--http',
  '--path', LEGO_CONF_PATH,
  *lego_conf_domains.map { ['--domains', _1] },
  'run',
].join(' ')
lego_renew_cmdline = [
  "#{BIN_ROOT}/lego",
  '--accept-tos',
  '--email', LEGO_CONF_EMAIL,
  '--http',
  '--http.webroot', LEGO_CONF_WEBROOT,
  '--path', LEGO_CONF_PATH,
  *lego_conf_domains.map { ['--domains', _1] },
  'renew',
  '--renew-hook', "'systemctl restart mioproxy'",
].join(' ')

http_request "#{BIN_ROOT}/lego.tar.gz" do
  url lego_tarball_url
end

execute "tar xf #{BIN_ROOT}/lego.tar.gz -C #{BIN_ROOT}"

file "#{BIN_ROOT}/CHANGELOG.md" do action :delete end
file "#{BIN_ROOT}/LICENSE" do action :delete end
file "#{BIN_ROOT}/lego.tar.gz" do action :delete end

execute lego_run_cmdline do
  not_if "test -f '#{LEGO_CONF_PATH}/certificates/#{lego_conf_primary_domain}.crt' -a -f '#{LEGO_CONF_PATH}/certificates/#{lego_conf_primary_domain}.key'"
end

execute "docker run --rm golang:#{GOLANG_VERSION} sh -c 'go install github.com/nsfisis/mioproxy@#{MIOPROXY_VERSION}; cat \"$(go env GOPATH)/bin/mioproxy\"' > #{BIN_ROOT}/mioproxy"

file "#{BIN_ROOT}/mioproxy" do
  mode '755'
end

file '/etc/systemd/system/mioproxy.service' do
  content <<~EOS
    [Unit]
    Description=MioProxy

    [Service]
    ExecStart=#{BIN_ROOT}/mioproxy #{REPO_ROOT}/mioproxy.prod.hcl
    Restart=always
    User=root
    Group=root
    WorkingDirectory=#{REPO_ROOT}

    [Install]
    WantedBy=multi-user.target
  EOS
end

service 'mioproxy.service' do
  action [:enable, :start]
end

file '/etc/systemd/system/lego-renew.service' do
  content <<~EOS
    [Unit]
    Description=Lego Renew

    [Service]
    Type=oneshot
    ExecStart=#{lego_renew_cmdline}
    User=root
    Group=root
  EOS
end

file '/etc/systemd/system/lego-renew.timer' do
  content <<~EOS
    [Unit]
    Description=Lego Renew Timer

    [Timer]
    Persistent=true
    OnCalendar=*-*-* 1:23
    RandomizedDelaySec=1h

    [Install]
    WantedBy=timers.target
  EOS
end

service 'lego-renew.timer' do
  action [:enable, :start]
end

# ken  ALL=(ALL:ALL) NOPASSWD:  /usr/bin/systemctl status mioproxy, /usr/bin/systemctl start mioproxy, /usr/bin/systemctl stop mioproxy, /usr/bin/systemctl restart mioproxy
