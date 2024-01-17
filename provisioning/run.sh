set -eu

this_dir="$(cd "$(dirname "$0")"; pwd)"
export BIN_ROOT="${this_dir}/.bin"
export REPO_ROOT="$(cd "${this_dir}/.."; pwd)"

export MITAMAE_VERSION=v1.14.0
export MITAMAE_ARCH=x86_64-linux
export LEGO_VERSION=v4.14.2
export LEGO_ARCH=amd64
export LEGO_CONF_EMAIL=nsfisis@gmail.com
export LEGO_CONF_WEBROOT="${REPO_ROOT}/letsencrypt/webroot"
export LEGO_CONF_PATH="${REPO_ROOT}/letsencrypt/lego"
export LEGO_CONF_DOMAINS="\
nil.ninja,\
t.nil.ninja"
export GOLANG_VERSION=1.21.1
export MIOPROXY_VERSION=v0.2.1

mitamae_bin_url="https://github.com/itamae-kitchen/mitamae/releases/download/${MITAMAE_VERSION}/mitamae-${MITAMAE_ARCH}.tar.gz"

rm -rf "${BIN_ROOT}"
mkdir "${BIN_ROOT}"

curl -L -o "${BIN_ROOT}/mitamae.tar.gz" "${mitamae_bin_url}"
tar xf "${BIN_ROOT}/mitamae.tar.gz" -C "${BIN_ROOT}"
mv "${BIN_ROOT}/mitamae-${MITAMAE_ARCH}" "${BIN_ROOT}/mitamae"
rm -f '${BIN_ROOT}/mitamae.tar.gz'

"${BIN_ROOT}/mitamae" local "${this_dir}/recipe.rb"
