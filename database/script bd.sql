CREATE TABLE users (
  Id int NOT NULL AUTO_INCREMENT,
  UserName varchar(20) NOT NULL,
  Email varchar(256) NOT NULL,
  Empresa int NULL,
  Status char(1) NOT NULL,
  PasswordHash varchar(500) NOT NULL,
  PRIMARY KEY (Id)
);

CREATE TABLE roles (
  Id int NOT NULL AUTO_INCREMENT,
  Name varchar(256) NOT NULL,
  PRIMARY KEY (Id)
);

CREATE TABLE userroles (
  UserId int NOT NULL,
  RoleId int NOT NULL,
  PRIMARY KEY (UserId, RoleId)
);

ALTER TABLE userroles
ADD CONSTRAINT FK_User FOREIGN KEY (UserId) REFERENCES users(Id) ON DELETE CASCADE;

ALTER TABLE userroles
ADD CONSTRAINT FK_Role FOREIGN KEY (RoleId) REFERENCES roles(Id) ON DELETE CASCADE;

-- -----

CREATE TABLE pessoa (
  Id int NOT NULL AUTO_INCREMENT,
  Nome varchar(20) NULL,
  Idade int NULL,
  Cidade varchar(100) NULL,
  Estado varchar(2) NULL,
  EmpresaId int null,
  TipoId int null,
  UserId int null
  PRIMARY KEY (Id)
);

ALTER TABLE pessoa
ADD CONSTRAINT Fk_Pessoa FOREIGN KEY (UserId) REFERENCES users (Id) ON DELETE CASCADE;
