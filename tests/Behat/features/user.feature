@user
Feature: Interacting with the REST API for users
    In order to interact with REST API for users
    As a user
    I want to make sure the Redmine server replies with the correct response

    Scenario: Creating an user
        Given I have a "NativeCurlClient" client
        When I create a user with the following data
            | property          | value                |
            | login             | username             |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.com     |
        Then the response has the status code "201"
        And the response has the content type "application/xml"
        And the returned data is an instance of "SimpleXMLElement"
        And the returned data has only the following properties
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            api_key
            status
            """
        And the returned data has proterties with the following data
            | property          | value                |
            | id                | 5                    |
            | login             | username             |
            | admin             | false                |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.com     |
            | last_login_on     | []                   |
            | passwd_changed_on | []                   |
            | twofa_scheme      | []                   |
            | status            | 1                    |

    Scenario: Showing a user
        Given I have a "NativeCurlClient" client
        When I show the user with id "1"
        Then the response has the status code "200"
        And the response has the content type "application/json"
        And the returned data has only the following properties
            """
            user
            """
        And the returned data "user" property is an array
        And the returned data "user" property has only the following properties
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            api_key
            status
            groups
            memberships
            """
        And the returned data "user" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | login             | admin                |
            | admin             | true                 |
            | firstname         | Redmine              |
            | lastname          | Admin                |
            | mail              | admin@example.net    |
            | twofa_scheme      | null                 |
            | status            | 1                    |
            | groups            | []                   |
            | memberships       | []                   |

    @error
    Scenario: Showing a not existing user
        Given I have a "NativeCurlClient" client
        When I show the user with id "10"
        Then the response has the status code "404"
        And the response has the content type "application/json"
        And the response has the content ""
        And the returned data is false

    Scenario: Listing of multiple users
        Given I have a "NativeCurlClient" client
        And I create a user with the following data
            | property          | value                |
            | login             | username             |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.net     |
        When I list all users
        Then the response has the status code "200"
        And the response has the content type "application/json"
        And the returned data has only the following properties
            """
            users
            total_count
            offset
            limit
            """
        And the returned data has proterties with the following data
            | property          | value                |
            | total_count       | 2                    |
            | offset            | 0                    |
            | limit             | 25                   |
        And the returned data "users" property is an array
        And the returned data "users" property contains "2" items
        And the returned data "users.0" property is an array
        And the returned data "users.0" property has only the following properties with Redmine version ">= 6.0.0"
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            status
            """
        But the returned data "users.0" property has only the following properties with Redmine version "< 6.0.0"
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            """
        And the returned data "users.0" property contains the following data with Redmine version ">= 6.0.0"
            | property          | value                |
            | id                | 1                    |
            | login             | admin                |
            | admin             | true                 |
            | firstname         | Redmine              |
            | lastname          | Admin                |
            | mail              | admin@example.net    |
            | twofa_scheme      | null                 |
            | status            | 1                    |
        But the returned data "users.0" property contains the following data with Redmine version "< 6.0.0"
            | property          | value                |
            | id                | 1                    |
            | login             | admin                |
            | admin             | true                 |
            | firstname         | Redmine              |
            | lastname          | Admin                |
            | mail              | admin@example.net    |
            | twofa_scheme      | null                 |
        And the returned data "users.1" property is an array
        And the returned data "users.1" property has only the following properties with Redmine version ">= 6.0.0"
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            status
            """
        But the returned data "users.1" property has only the following properties with Redmine version "< 6.0.0"
            """
            id
            login
            admin
            firstname
            lastname
            mail
            created_on
            updated_on
            last_login_on
            passwd_changed_on
            twofa_scheme
            """
        And the returned data "users.1" property contains the following data with Redmine version ">= 6.0.0"
            | property          | value                |
            | id                | 5                    |
            | login             | username             |
            | admin             | false                |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.net     |
            | twofa_scheme      | null                 |
            | status            | 1                    |
        And the returned data "users.1" property contains the following data with Redmine version "< 6.0.0"
            | property          | value                |
            | id                | 5                    |
            | login             | username             |
            | admin             | false                |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.net     |
            | twofa_scheme      | null                 |

    Scenario: Listing of multiple user logins
        Given I have a "NativeCurlClient" client
        And I create a user with the following data
            | property          | value                |
            | login             | username             |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.net     |
        When I list all user logins
        Then the response has the status code "200"
        And the response has the content type "application/json"
        And the returned data contains "2" items
        And the returned data has proterties with the following data
            | property          | value                |
            | 1                 | admin                |
            | 5                 | username             |

    Scenario: Listing of multiple user logins
        Given I have a "NativeCurlClient" client
        And I create "108" users
        When I list all user logins
        Then the response has the status code "200"
        And the response has the content type "application/json"
        And the returned data contains "109" items

    Scenario: Updating an user
        Given I have a "NativeCurlClient" client
        And I create a user with the following data
            | property          | value                |
            | login             | username             |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.com     |
        When I update the user with id "5" and the following data
            | property          | value                |
            | firstname         | new_first            |
            | lastname          | new_last             |
            | mail              | new_mail@example.com |
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""

    Scenario: Removing an user
        Given I have a "NativeCurlClient" client
        And I create a user with the following data
            | property          | value                |
            | login             | username             |
            | firstname         | first                |
            | lastname          | last                 |
            | mail              | mail@example.com     |
        When I remove the user with id "5"
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""
