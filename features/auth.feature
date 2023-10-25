Feature:
    In order to issue authenticated requests
    As a user
    I want to obtain an access token via json login

    Scenario: It receives a JWT upon successful json login
        When I send a "POST" request to "/api/login" with body:
        """
        {
            "username": "john.smith@example.org",
            "password": "pwned1234"
        }
        """
        Then the response status code should be 200
        And the response is valid json
        And I should see a valid JWT for "john.smith@example.org" in the "token" field of the response

    Scenario: It returns unauthorized for a json login with invalid password
        When I send a "POST" request to "/api/login" with body:
        """
        {
            "username": "john.smith@example.org",
            "password": "incorrect"
        }
        """
        Then the response status code should be 401

    Scenario: It returns unauthorized for a json login with an unknown user
        When I send a "POST" request to "/api/login" with body:
        """
        {
            "username": "notexists@example.org",
            "password": "pwned1234"
        }
        """
        Then the response status code should be 401
