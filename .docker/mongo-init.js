db = db.getSiblingDB('diagnostic');

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
            },
        },
        validationLevel: "strict",
        validationAction: "error"
    }
);

db.createCollection('another_test');

db.runCommand(
    {
        collMod: 'another_test',
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
            },
        },
        validationLevel: "strict",
        validationAction: "error"
    }
);