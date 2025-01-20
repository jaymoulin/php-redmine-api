@issue
Feature: Interacting with the REST API for issues
    In order to interact with REST API for issues
    As a user
    I want to make sure the Redmine server replies with the correct response

    Scenario: Creating an issue with miminal data
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        When I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        Then the response has the status code "201"
        And the response has the content type "application/xml"
        And the returned data is an instance of "SimpleXMLElement"
        And the returned data has only the following properties
            """
            id
            project
            tracker
            status
            priority
            author
            subject
            description
            start_date
            due_date
            done_ratio
            is_private
            estimated_hours
            total_estimated_hours
            created_on
            updated_on
            closed_on
            """
        And the returned data has proterties with the following data
            | property              | value                |
            | id                    | 1                    |
            | subject               | issue subject        |
            | description           | []                   |
            | due_date              | []                   |
            | done_ratio            | 0                    |
            | is_private            | false                |
            | estimated_hours       | []                   |
            | total_estimated_hours | []                   |
        And the returned data "project" property is an array
        And the returned data "project" property contains "1" items
        And the returned data "project.@attributes" property is an array
        And the returned data "project.@attributes" property has only the following properties
            """
            id
            name
            """
        And the returned data "project.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | Test Project         |
        And the returned data "tracker" property is an array
        And the returned data "tracker" property contains "1" items
        And the returned data "tracker.@attributes" property is an array
        And the returned data "tracker.@attributes" property has only the following properties
            """
            id
            name
            """
        And the returned data "tracker.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | Defect               |
        And the returned data "status" property is an array
        And the returned data "status" property contains "1" items
        And the returned data "status.@attributes" property is an array
        And the returned data "status.@attributes" property has only the following properties
            """
            id
            name
            is_closed
            """
        And the returned data "status.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | New                  |
            | is_closed         | false                |
        And the returned data "priority" property is an array
        And the returned data "priority" property contains "1" items
        And the returned data "priority.@attributes" property is an array
        And the returned data "priority.@attributes" property has only the following properties
            """
            id
            name
            """
        And the returned data "priority.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | Normal               |
        And the returned data "author" property is an array
        And the returned data "author" property contains "1" items
        And the returned data "author.@attributes" property is an array
        And the returned data "author.@attributes" property has only the following properties
            """
            id
            name
            """
        And the returned data "author.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | Redmine Admin        |

    @custom_field
    Scenario: Creating an issue with custom field
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        And I create a custom field for issues with the name "Note"
        And I enable the tracker with ID "1" for custom field with ID "1"
        When I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
            | custom_fields     | [{"id":1,"value":"Note for custom field"}] |
        Then the response has the status code "201"
        And the response has the content type "application/xml"
        And the returned data is an instance of "SimpleXMLElement"
        And the returned data has only the following properties
            """
            id
            project
            tracker
            status
            priority
            author
            subject
            description
            start_date
            due_date
            done_ratio
            is_private
            estimated_hours
            total_estimated_hours
            custom_fields
            created_on
            updated_on
            closed_on
            """
        And the returned data has proterties with the following data
            | property              | value                |
            | id                    | 1                    |
            | subject               | issue subject        |
            | description           | []                   |
            | due_date              | []                   |
            | done_ratio            | 0                    |
            | is_private            | false                |
            | estimated_hours       | []                   |
            | total_estimated_hours | []                   |
        And the returned data "custom_fields.custom_field" property has only the following properties
            """
            @attributes
            value
            """
        And the returned data "custom_fields.custom_field.@attributes" property contains the following data
            | property          | value                |
            | id                | 1                    |
            | name              | Note                 |
        And the returned data "custom_fields.custom_field" property contains the following data
            | property          | value                |
            | value             | Note for custom field |

    Scenario: Updating an issue
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        And I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        When I update the issue with id "1" and the following data
            | property          | value                |
            | subject           | new issue subject    |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""

    @error
    Scenario: Showing a not existing issue
        Given I have a "NativeCurlClient" client
        When I show the issue with id "10"
        Then the response has the status code "404"
        And the response has the content type "application/json"
        And the response has the content ""
        And the returned data is false

    Scenario: Adding a watcher to an issue
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        And I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        When I add the user id "1" as a watcher to the issue with id "1"
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""

    Scenario: Removing a watcher from an issue
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        And I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        And I add the user id "1" as a watcher to the issue with id "1"
        When I remove the user id "1" as a watcher from the issue with id "1"
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""

    Scenario: Removing an issue
        Given I have a "NativeCurlClient" client
        And I have an issue status with the name "New"
        And I have an issue priority with the name "Normal"
        And I have a tracker with the name "Defect" and default status id "1"
        And I create a project with name "Test Project" and identifier "test-project"
        And I create an issue with the following data
            | property          | value                |
            | subject           | issue subject        |
            | project           | Test Project         |
            | tracker           | Defect               |
            | priority          | Normal               |
            | status            | New                  |
        When I remove the issue with id "1"
        Then the response has the status code "204"
        And the response has an empty content type
        And the response has the content ""
        And the returned data is exactly ""
