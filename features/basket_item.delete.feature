@default @basket @basket_item
Feature:
    In order collect interesting products
    As a guest
    I need to be able to remove an Item from my Basket when I change my mind

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

    Scenario: It removes a Basket Item of a guest
        When I set the request header "X-GUEST-TOKEN" to: "e50e542b-5206-489e-bf0b-79e2e2b0338b"
        And I send a "DELETE" request to "/api/basket/95386f2f-2e68-490f-9dc7-3fd7905c6e4f/item/32e4f1b4-c102-41d3-bb5a-b692cf498e01"
        Then the response status code should be 204
        And the response should be empty
        And the basket item entity with identifier "32e4f1b4-c102-41d3-bb5a-b692cf498e01" should have been deleted

