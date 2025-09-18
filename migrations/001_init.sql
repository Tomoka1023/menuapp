-- ユーザー
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- レシピ（1品）
CREATE TABLE recipes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  servings INT NOT NULL DEFAULT 2,              -- 基準人数
  instructions TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- レシピ材料（正規化しすぎない：名前＋数量＋単位）
CREATE TABLE recipe_ingredients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,                   -- 例: 玉ねぎ
  quantity DECIMAL(10,2) NOT NULL,              -- 例: 0.5
  unit VARCHAR(50) NOT NULL,                    -- 例: 個, g, ml, 大さじ, 小さじ
  note VARCHAR(255),                            -- みじん切り 等
  FOREIGN KEY (recipe_id) REFERENCES recipes(id)
);

-- 週間献立の枠（1日×食事区分ごとに1件）
CREATE TABLE meal_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,                           -- その日の枠
  meal ENUM('breakfast','lunch','dinner') NOT NULL,
  recipe_id INT NOT NULL,
  servings INT NOT NULL,                        -- その枠で作る人数（家族人数等）
  UNIQUE KEY uq_user_date_meal (user_id, date, meal),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (recipe_id) REFERENCES recipes(id)
);

-- パントリー（常備食材／家にある在庫）
CREATE TABLE pantry_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,                   -- 例: 砂糖
  unit VARCHAR(50) NOT NULL,                    -- 例: g
  quantity DECIMAL(10,2) NOT NULL DEFAULT 0,    -- 家にある量
  UNIQUE KEY uq_user_name_unit (user_id, name, unit),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
