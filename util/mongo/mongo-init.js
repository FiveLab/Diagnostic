db = db.getSiblingDB('diagnostic');

db.createUser(
    {
        user: 'user',
        pwd: 'pass',
        roles: [{ role: 'readWrite', db: 'diagnostic' }],
    },
);

db.createCollection('test');

db.runCommand(
    {
        collMod: 'test',
        validator: {
            $jsonSchema: {
                required: [ "a", "b", "c" ],
                properties: {
                    a: {
                        bsonType: "string",
                    },
                    b: {
                        bsonType: "string",
                    },
                    c: {
                        bsonType: "string",
                    },
                }
            }
        }
    }
);