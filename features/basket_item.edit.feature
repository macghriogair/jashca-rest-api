@default @basket @basket_item
Feature:
    In order collect interesting products
    As a guest
    I need to be able to edit an Item in my Basket when I change my mind

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
        And given a basket item with the following attributes:
            | identifier | 32e4f1b4-c102-41d3-bb5a-b692cf498e01 |
            | basket | 95386f2f-2e68-490f-9dc7-3fd7905c6e4f |
            | product | c5d0b849-2fd2-4683-b436-d2bdb424b925 |
            | quantity | 2 |

    Scenario: It changes the quantity of a Basket Item of a guest
        When I set the request header "X-GUEST-TOKEN" to: "e50e542b-5206-489e-bf0b-79e2e2b0338b"
        And I send a "PUT" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f/item/32e4f1b4-c102-41d3-bb5a-b692cf498e01" with body:
        """
        {
            "amount": 4
        }
        """
        Then the response status code should be 200
        And the response json should be:
        """
        {
            "id": "32e4f1b4-c102-41d3-bb5a-b692cf498e01",
            "product": {
                "id": "c5d0b849-2fd2-4683-b436-d2bdb424b925",
                "name": "Book of Exalted Deeds",
                "price": {
                    "value": 10500,
                    "currency": "EUR",
                    "vat": 19
                },
                "amountAvailable": 1,
                "extra": {
                    "stockStatus": "W_LOW_STOCK_AVAILABILITY"
                }
            },
            "amount": 4
        }
        """

    Scenario: It not updates a Basket Item if the requested quantity exceeds the available stock
        When I set the request header "X-GUEST-TOKEN" to: "e50e542b-5206-489e-bf0b-79e2e2b0338b"
        And I send a "PUT" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f/item/32e4f1b4-c102-41d3-bb5a-b692cf498e01" with body:
        """
        {
            "amount": 6
        }
        """
        Then the response status code should be 422
        And the error message is: "Not enough product items in stock. Product c5d0b849-2fd2-4683-b436-d2bdb424b925"

    Scenario: An Item is not updated for a guest with wrong guest token
        When I set the request header "X-GUEST-TOKEN" to: "not-exists"
        And I send a "PUT" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f/item/32e4f1b4-c102-41d3-bb5a-b692cf498e01" with body:
        """
        {
            "amount": 3
        }
        """
        Then the response status code should be 403
