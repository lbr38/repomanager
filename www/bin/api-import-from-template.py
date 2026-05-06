#!/usr/bin/python3
# coding: utf-8
# Script to import source repositories from a YAML template file to Repomanager using the API.
# The script can also update the SSL certificate, private key and CA certificate in the template before importing it to Repomanager, 
# by providing direct paths to the files or a directory with patterns to match them.

# Import libraries
import yaml
import requests
import argparse
import glob
import re
from pathlib import Path

# Define a custom class for multiline strings
class LiteralString(str):
    pass

# Define a custom presenter for multiline strings
def literal_string_presenter(dumper, data):
    return dumper.represent_scalar('tag:yaml.org,2002:str', data, style='|')

# Print help
def help():
    print('Usage: api-import-from-template.py [OPTIONS]')
    print('Options:')
    print('\n  Repomanager:')
    print('  --url                          URL to Repomanager. e.g. https://repomanager.example.com')
    print('  --api-token                    API token to authenticate to Repomanager')

    print('\n  Template:')
    print('  --template-path                Path to the source repository template to import')
    print('  --repository-type              Type of repository included in the template (rpm or deb)')

    print('\n  Certificates and private key paths. You can provide direct paths to the files, specify a directory and patterns to match the files or a combination of both')
    print('  --certs-dir                    Directory where the certificates are stored')
    print('  --certificate-pattern          Pattern to match the certificate in the certs directory. Pattern can be a regular expression (e.g. "*.crt$")')
    print('  --private-key-pattern          Pattern to match the private key in the certs directory. Pattern can be a regular expression (e.g. "*.key$")')
    print('  --ca-certificate-pattern       Pattern to match the CA certificate in the certs directory. Pattern can be a regular expression (e.g. "*.crt$")')
    print('  --certificate-path             Direct path to the certificate file')
    print('  --private-key-path             Direct path to the private key file')
    print('  --ca-certificate-path          Direct path to the CA certificate file')

# Parse arguments
def parse_arguments():
    parser = argparse.ArgumentParser(add_help=False)

    # Help
    parser.add_argument("--help", action="store_true", default='null')
    # Directory where the certificates are stored
    parser.add_argument("--certs-dir", action="store", nargs='?', default='null')
    # Pattern to match the certificate
    parser.add_argument("--certificate-pattern", action="store", nargs='?', default='null')
    # Pattern to match the private key
    parser.add_argument("--private-key-pattern", action="store", nargs='?', default='null')
    # Pattern to match the CA certificate
    parser.add_argument("--ca-certificate-pattern", action="store", nargs='?', default='null')

    # Direct path to certificate
    parser.add_argument("--certificate-path", action="store", nargs='?', default='null')
    # Direct path to private key
    parser.add_argument("--private-key-path", action="store", nargs='?', default='null')
    # Direct path to CA certificate
    parser.add_argument("--ca-certificate-path", action="store", nargs='?', default='null')

    # Template file
    parser.add_argument("--template-path", action="store", nargs='?', default='null')
    # Repository type
    parser.add_argument("--repository-type", action="store", nargs='?', default='null')

    # URL to Repomanager
    parser.add_argument("--url", action="store", nargs='?', default='null')
    # API token
    parser.add_argument("--api-token", action="store", nargs='?', default='null')

    # Parse arguments
    args, remaining_args = parser.parse_known_args()

    if remaining_args:
        raise Exception('Unknown argument(s): ' + str(remaining_args))

    # If --help is set, print help and exit
    if args.help != 'null':
        help()
        exit(0)

    # Check that --template is set
    if args.template_path == 'null':
        raise Exception('You must specify --template')

    # Check that --repository-type is set
    if args.repository_type == 'null':
        raise Exception('You must specify --repository-type')

    # Check that --url is set
    if args.url == 'null':
        raise Exception('You must specify --url')

    # Check that --api-token is set
    if args.api_token == 'null':
        raise Exception('You must specify --api-token')
    
    # Check that at least --certificate-pattern or --certificate are set
    if args.certificate_pattern == 'null' and args.certificate_path == 'null':
        raise Exception('You must specify either --certificate-pattern or --certificate-path')

    # Check that at least --private-key-pattern or --private-key are set
    if args.private_key_pattern == 'null' and args.private_key_path == 'null':
        raise Exception('You must specify either --private-key-pattern or --private-key-path')

    # If a pattern is set, check that --certs-dir is set
    if (args.certificate_pattern != 'null' or args.private_key_pattern != 'null' or args.ca_certificate_pattern != 'null') and args.certs_dir == 'null':
        raise Exception('You must specify --certs-dir when using --certificate-pattern, --private-key-pattern or --ca-certificate-pattern')

    return args


# Main execution
try:
    # Initialize variables
    certificate_path = None
    private_key_path = None
    ca_certificate_path = None
    certificate_content = ''
    private_key_content = ''
    ca_certificate_content = ''

    # Add the custom class to the YAML representer
    yaml.add_representer(LiteralString, literal_string_presenter)

    # Parse and retrieve arguments
    args = parse_arguments()
    template_path = args.template_path
    repository_type = args.repository_type
    url = args.url.rstrip('/')
    api_token = args.api_token

    # If direct paths to certificates and private key are provided, use them
    if args.certificate_path != 'null':
        certificate_path = args.certificate_path
    if args.private_key_path != 'null':
        private_key_path = args.private_key_path
    if args.ca_certificate_path != 'null':
        ca_certificate_path = args.ca_certificate_path

    # If --certs-dir, try to find the certificates
    if args.certs_dir != 'null':
        # First, check that directory exists
        if not Path(args.certs_dir).is_dir():
            raise Exception('Directory ' + args.certs_dir + ' does not exist')

        # Get all files in the certs directory
        files = glob.glob(args.certs_dir + '/*')

        # Find the certificates and private key using the patterns
        # unless direct paths have been provided

        # Find certificate
        if args.certificate_pattern != 'null' and args.certificate_path == 'null':
            # Find certificate
            for file in files:
                if re.search(args.certificate_pattern, file):
                    certificate_path = file
                    print('Certificate found: ' + certificate_path)
                    break

        # Find private key
        if args.private_key_pattern != 'null' and args.private_key_path == 'null':
            for file in files:
                if re.search(args.private_key_pattern, file):
                    private_key_path = file
                    print('Private key found: ' + private_key_path)
                    break

        # Find CA certificate
        if args.ca_certificate_pattern != 'null' and args.ca_certificate_path == 'null':
            for file in files:
                if re.search(args.ca_certificate_pattern, file):
                    ca_certificate_path = file
                    print('CA certificate found: ' + ca_certificate_path)
                    break

        # Check that all certificates were found
        if certificate_path == None:
            raise Exception('Certificate not found in ' + args.certs_dir + ' with pattern ' + args.certificate_pattern)
        if private_key_path == None:
            raise Exception('Private key not found in ' + args.certs_dir + ' with pattern ' + args.private_key_pattern)
        if args.ca_certificate_pattern != 'null' and ca_certificate_path == None:
            raise Exception('CA certificate not found in ' + args.certs_dir + ' with pattern ' + args.ca_certificate_pattern)
        
    # Check that certificates and private key files exist
    if not Path(certificate_path).is_file():
        raise Exception('Certificate file not found: ' + certificate_path)
    if not Path(private_key_path).is_file():
        raise Exception('Private key file not found: ' + private_key_path)
    if ca_certificate_path != None and not Path(ca_certificate_path).is_file():
        raise Exception('CA certificate file not found: ' + ca_certificate_path)

    # Retrieve certificate content
    with open(certificate_path, 'r') as file:
        certificate_content = file.read()
        # Raise an exception if the certificate is empty
        if not certificate_content:
            raise Exception('certificate file is empty: ' + certificate_path)

    # Retrieve private key content
    with open(private_key_path, 'r') as file:
        private_key_content = file.read()
        # Raise an exception if the private key is empty
        if not private_key_content:
            raise Exception('private key file is empty: ' + private_key_path)

    # Retrieve CA certificate content
    if ca_certificate_path != None:
        with open(ca_certificate_path, 'r') as file:
            ca_certificate_content = file.read()
            # Raise an exception if the CA certificate is empty
            if not ca_certificate_content:
                raise Exception('CA certificate file is empty: ' + ca_certificate_path)

    # Retrieve source repos template
    with open(template_path, 'r') as file:
        try:
            # Read YAML and return configuration
            template = yaml.safe_load(file)
        except yaml.YAMLError as e:
            raise Exception(str(e))
        
        # Raise an exception if the template is empty
        if not template:
            raise Exception('template file is empty: ' + template)

    # Check if 'repositories' key exists in template
    if 'repositories' not in template:
        raise Exception('repositories key not found in the template: ' + template)
    
    # Update template
    for repo in template['repositories']:
        # Check if 'ssl-authentication' key exists in repo
        # If it exists, update certificate, private key and CA certificate, using LiteralString (|) for multiline strings
        if 'ssl-authentication' in repo:
            repo['ssl-authentication']['certificate'] = LiteralString(certificate_content)
            repo['ssl-authentication']['private-key'] = LiteralString(private_key_content)
            repo['ssl-authentication']['ca-certificate'] = LiteralString(ca_certificate_content)
    
    # Save updated template
    try:
        with open(template_path + '.output', 'w') as file:
            # TODO: sort_keys=False not working on older versions of PyYAML
            # yaml.dump(template, file, default_flow_style=False, sort_keys=False)
            yaml.dump(template, file, default_flow_style=False)
    except Exception as e:
        raise Exception('Could not save updated template: ' + str(e))
    
    # Send updated template to Repomanager API
    try:
        # Send a POST request to the API with the updated template
        response = requests.post(
            # The URL to send the request
            url + '/api/v2/source/' + repository_type + '/import/',
            # The template file to send
            files = {
                'template': open(template_path + '.output', 'rb')
            },
            # The token to authenticate
            headers = {
                'Authorization': 'Bearer ' + api_token,
            },
            # Timeout for the request
            timeout = (5, 3))

        # Check is response is OK (20x)
        response.raise_for_status()

        # Print the response
        print(response.json())

    # If the response is not OK, raise an exception
    except requests.exceptions.HTTPError as e:
        raise Exception(response.json())

except Exception as e:
    print(str(e))
    exit(1)

exit(0)
