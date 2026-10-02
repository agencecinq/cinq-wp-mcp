<?php
/**
 * Application passwords sent in a header.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Auth\ApiKeyAuthentication;

section( 'Reading credentials' );

check( 'an API key header yields a login and a password', array( 'login' => 'joachim', 'password' => 'abcd EFGH ijkl MNOP' ) === ApiKeyAuthentication::parse_credentials( '  joachim:abcd EFGH ijkl MNOP  ' ) );
check( 'a Basic authorization header is decoded', array( 'login' => 'joachim', 'password' => 'abcd EFGH' ) === ApiKeyAuthentication::parse_credentials( 'Basic ' . base64_encode( 'joachim:abcd EFGH' ) ) );
check( 'the Basic scheme is matched case-insensitively', array( 'login' => 'joachim', 'password' => 'secret' ) === ApiKeyAuthentication::parse_credentials( 'basic ' . base64_encode( 'joachim:secret' ) ) );
check( 'undecodable base64 is rejected', null === ApiKeyAuthentication::parse_credentials( 'Basic !!!not base64!!!' ) );
check( 'a Bearer token is left to OAuth', null === ApiKeyAuthentication::parse_credentials( 'Bearer cwa_abcdef' ) );
check( 'a password containing a colon is kept whole', 'a:b' === ( ApiKeyAuthentication::parse_credentials( 'joachim:a:b' )['password'] ?? null ) );
check( 'a header with no colon is rejected', null === ApiKeyAuthentication::parse_credentials( 'joachim' ) );
check( 'an empty login is rejected', null === ApiKeyAuthentication::parse_credentials( ':secret' ) );
check( 'an empty password is rejected', null === ApiKeyAuthentication::parse_credentials( 'joachim:   ' ) );
check( 'a missing header is rejected', null === ApiKeyAuthentication::parse_credentials( null ) );
check( 'every accepted header name is on the approved list', array( 'x-api-key', 'x-auth-token', 'authorization' ) === array_map( 'strtolower', array_keys( ApiKeyAuthentication::HEADERS ) ) );

section( 'Only on the MCP route' );

$route = 'wp-json/cinq-wp-mcp/mcp';

check( 'the route matches at the site root', ApiKeyAuthentication::matches_route( '/wp-json/cinq-wp-mcp/mcp', $route ) );
check( 'the route matches in a subdirectory install', ApiKeyAuthentication::matches_route( '/blog/wp-json/cinq-wp-mcp/mcp', $route ) );
check( 'a trailing slash still matches', ApiKeyAuthentication::matches_route( '/wp-json/cinq-wp-mcp/mcp/', $route ) );
check( 'another REST route does not match', ! ApiKeyAuthentication::matches_route( '/wp-json/wp/v2/posts', $route ) );
check( 'the OAuth routes do not match', ! ApiKeyAuthentication::matches_route( '/wp-json/cinq-wp-mcp/oauth/token', $route ) );
check( 'an empty path does not match', ! ApiKeyAuthentication::matches_route( '', $route ) );
