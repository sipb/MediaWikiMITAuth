-- Table that links MediaWiki accounts of the users and their external accounts,
-- which may be MIT usernames or whatever else we decide to add in the future.
-- A MediaWiki user account may be linked only to one outside entity.
CREATE TABLE /*_*/mit_account_links (
  -- MediaWiki user ID of the account
  mal_user_id int unsigned NOT NULL,

  -- Linked username or other sort of identifier
  mal_linked_name varchar(255) binary NOT NULL,
  
  -- To what sort of account are we linking. Currently the only supported value is 'MIT'
  mal_linked_provider varchar(32) binary NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/mal_user_id ON /*_*/mit_account_links (mal_user_id);
CREATE UNIQUE INDEX /*i*/mal_linked ON /*_*/mit_account_links (mal_linked_provider,mal_linked_name);
