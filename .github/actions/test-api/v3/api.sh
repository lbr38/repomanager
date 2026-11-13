# Helpers for the API v3 tests, API_HOST and API_KEY must be set

# Send a request to the API v3: api <method> <path> [curl options...]
api() {
    local method="$1"
    local path="$2"
    shift 2

    curl --fail-with-body -L -s --post301 -X "$method" -H "Authorization: Bearer $API_KEY" "$@" "$API_HOST/api/v3$path"
}

# Send a request that must succeed and print its result: must_pass <method> <path> [curl options...]
must_pass() {
    local output

    if ! output=$(api "$@"); then
        echo "Request failed: $1 $2"
        echo "$output"
        exit 1
    fi

    echo "$output" | jq .
}

# Send a request that must fail with the specified HTTP status code and error code (Problem Details, RFC 9457)
# must_fail <status> <error code> <method> <path> [curl options...]
must_fail() {
    local status="$1"
    local error_code="$2"
    local method="$3"
    local path="$4"
    local code
    shift 4

    code=$(curl -L -s --post301 -o /tmp/api-v3-output -w '%{http_code}' -X "$method" -H "Authorization: Bearer $API_KEY" "$@" "$API_HOST/api/v3$path")

    if [ "$code" != "$status" ] || [ "$(jq -r '.code' /tmp/api-v3-output)" != "$error_code" ]; then
        echo "Expected HTTP $status and error code $error_code, got HTTP $code: $method $path"
        cat /tmp/api-v3-output
        exit 1
    fi

    echo "Correctly failed with HTTP $status $error_code ($method $path): $(jq -r '.detail' /tmp/api-v3-output)"
}

# Check a JSON result with a jq expression: check '<json>' '<jq expression>'
check() {
    if ! echo "$1" | jq -e "$2" > /dev/null; then
        echo "Unexpected result, '$2' is false:"
        echo "$1" | jq .
        exit 1
    fi
}

# Copy the test packages under a 'v3-' prefixed name, so that they are not already uploaded by the API v2 tests
# Set DEB_PACKAGE and RPM_PACKAGE variables
prepare_packages() {
    mkdir -p "v3-$HOSTNAME"

    for package in "$HOSTNAME"/*nginx_1.26.0-1~bookworm_amd64.deb "$HOSTNAME"/*nginx-1.26.0-1.el7.ngx.x86_64.rpm; do
        cp "$package" "v3-$HOSTNAME/v3-$(basename "$package")"
    done

    DEB_PACKAGE=$(ls -A1 "v3-$HOSTNAME"/*nginx_1.26.0-1~bookworm_amd64.deb | head -1)
    RPM_PACKAGE=$(ls -A1 "v3-$HOSTNAME"/*nginx-1.26.0-1.el7.ngx.x86_64.rpm | head -1)
}
