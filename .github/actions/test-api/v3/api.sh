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

# Wait for a task to end, it must end with the 'done' status: wait_task <task Id> [timeout in seconds]
wait_task() {
    local id="$1"
    local timeout="${2:-600}"
    local elapsed=0
    local status=""

    while [ "$elapsed" -lt "$timeout" ]; do
        status=$(api GET "/tasks/$id" | jq -r '.data.status')

        case "$status" in
            done)
                echo "Task #$id done after ${elapsed}s"
                return 0
                ;;
            error|stopped)
                echo "Task #$id ended with status '$status':"
                api GET "/tasks/$id" | jq .
                exit 1
                ;;
        esac

        sleep 5
        elapsed=$((elapsed + 5))
    done

    echo "Task #$id did not end after ${timeout}s (status '$status')"
    exit 1
}

# Launch a task and wait for it to end with the 'done' status: run_task <timeout in seconds> <method> <path> [curl options...]
run_task() {
    local timeout="$1"
    local output
    shift

    if ! output=$(api "$@"); then
        echo "Request failed: $1 $2"
        echo "$output"
        exit 1
    fi

    echo "$output" | jq .
    wait_task "$(echo "$output" | jq -r '.data.id')" "$timeout"
}

# Print the Id of a repository from its name, fail if it does not exist: repo_id <type> <name>
repo_id() {
    local id

    id=$(api GET "/repositories/$1" | jq -r --arg name "$2" '[.data[] | select(.name == $name)][0].id // empty')

    if [ -z "$id" ]; then
        echo "Repository $1 '$2' not found" >&2
        exit 1
    fi

    echo "$id"
}

# Delete the repositories created by the lifecycle tests (named 'ci-v3-*'), including the ones left by a previous run that failed
cleanup_repositories() {
    local type
    local id

    for type in local curated mirror; do
        for id in $(api GET "/repositories/$type" | jq -r '.data[] | select(.name | startswith("ci-v3-")) | .id' | sort -u); do
            echo "Deleting $type repository #$id"
            run_task 600 DELETE "/repositories/$type/$id"
        done
    done
}
