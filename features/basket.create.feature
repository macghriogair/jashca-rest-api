@default @basket
Feature:
    In order collect interesting products
    As a guest
    I need an endpoint to create a new Basket

    Scenario: It creates an empty Basket for a guest
        When I send a "POST" request to "/api/basket" with body:
        """
        {
        }
        """
        Then the response status code should be 201
        And the response should be empty
        And the response header "Location" should be present
        And the "Location" header should point to the Resource URI under "/api/basket"
        And the response header "X-GUEST-TOKEN" should be present
        When I set the last response header "X-GUEST-TOKEN" in the request headers
        And I send a "GET" request to the last Resource URI
        Then the response status code should be 200

    Scenario: It creates a Basket with initial items for a guest
        Given a product with the following attributes:
            | identifier | c5d0b849-2fd2-4683-b436-d2bdb424b925 |
            | name | Book of Exalted Deeds |
            | price | 10500 |
            | stockQuantity | 1 |
        When I send a "POST" request to "/api/basket" with body:
        """
        {
            "items": [{"productId": "c5d0b849-2fd2-4683-b436-d2bdb424b925", "amount": 1}]
        }
        """
        Then the response status code should be 201
        And the response should be empty
        And the response header "Location" should be present
        And the "Location" header should point to the Resource URI under "/api/basket"
        And the response header "X-GUEST-TOKEN" should be present
        When I set the last response header "X-GUEST-TOKEN" in the request headers
        And I send a "GET" request to the last Resource URI
        Then the response status code should be 200
        And the basket should contain the scoped product with amount 1
