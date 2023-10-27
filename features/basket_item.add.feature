@default @basket @basket_item
Feature:
    In order collect interesting products
    As a guest
    I need to be able to add a Product to my Basket

    Background:
        Given a basket with the following attributes:
            | identifier | 95386f2f-2e68-490f-9dc7-3fd7905c6e4f |
            | guestToken | e50e542b-5206-489e-bf0b-79e2e2b0338b |
            | status | pending |
        And given a product with the following attributes:
            | identifier | c5d0b849-2fd2-4683-b436-d2bdb424b925 |
            | name | Book of Exalted Deeds |
            | price | 10500 |
            | stockQuantity | 5 |

    Scenario: It adds a new Item to an existing Basket of a guest
        When I set the request header "X-GUEST-TOKEN" to: "e50e542b-5206-489e-bf0b-79e2e2b0338b"
        And I send a "POST" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f" with body:
        """
        {
            "productId": "c5d0b849-2fd2-4683-b436-d2bdb424b925",
            "amount": 2
        }
        """
        Then the response status code should be 201
        And the response should be empty
        And the response header "Location" should be present
        And the "Location" header should point to the Resource URI under "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f/item"
        And I send a "GET" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f"
        Then the response status code should be 200
        And the basket should contain the scoped product with amount 2

    Scenario: An Item is not added if the target amount exceeds the available stock
        When I set the request header "X-GUEST-TOKEN" to: "e50e542b-5206-489e-bf0b-79e2e2b0338b"
        And I send a "POST" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f" with body:
        """
        {
            "productId": "c5d0b849-2fd2-4683-b436-d2bdb424b925",
            "amount": 6
        }
        """
        Then the response status code should be 422
        And the error message is: "Not enough product items in stock. Product c5d0b849-2fd2-4683-b436-d2bdb424b925"

    Scenario: An Item is not added for a guest with wrong guest token
        When I set the request header "X-GUEST-TOKEN" to: "231df61d-3b4c-4547-9167-c5fdc938b262"
        And I send a "POST" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f" with body:
        """
        {
            "productId": "c5d0b849-2fd2-4683-b436-d2bdb424b925",
            "amount": 2
        }
        """
        Then the response status code should be 403
