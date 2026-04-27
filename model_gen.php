<?php
/**
 * @file   model_gen.php
 * @brief  Generate model classes from table schema.
 */
require_once __DIR__ . '/includes/session.php';
requireLogin();

// ── Handle file-export actions BEFORE any HTML output ────────────
$exportAction = $_POST['export_action'] ?? '';

if ($exportAction === 'download' && !empty($_POST['export_code']) && !empty($_POST['export_filename'])) {
    $code     = $_POST['export_code'];
    $filename = basename($_POST['export_filename']); // sanitise
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($code));
    echo $code;
    exit;
}


$dbName = $_SESSION['db_name'] ?? '';
$db = getDbConnection();
$tables = $db ? $db->getAllTables($dbName) : [];

$selectedTable = $_POST['table'] ?? $_GET['table'] ?? '';
$language      = $_POST['language'] ?? $_GET['language'] ?? 'php';
$namespace     = $_POST['namespace'] ?? $_GET['namespace'] ?? 'App\\Models';
$style         = $_POST['style'] ?? $_GET['style'] ?? 'full'; // full | dataclass | activerecord
$regenerate    = isset($_GET['regenerate']);
$generatedCode = '';
$generatedFiles = [];

/**
 * Map SQL type string to PHP type hint.
 */
function sqlTypeToPhp(string $sqlType): string {
    $t = strtolower($sqlType);
    if (preg_match('/int|serial/i', $t))                        return 'int';
    if (preg_match('/float|double|real/i', $t))                 return 'float';
    if (preg_match('/decimal|numeric|money/i', $t))             return 'string'; // precision
    if (preg_match('/bool/i', $t))                              return 'bool';
    if (preg_match('/date|time|timestamp/i', $t))               return 'string';
    if (preg_match('/blob|binary|bytea|image/i', $t))           return 'string';
    return 'string';
}

/**
 * Convert snake_case table/column name to PascalCase.
 */
function toPascalCase(string $name): string {
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
}

/**
 * Convert snake_case to camelCase.
 */
function toCamelCase(string $name): string {
    return lcfirst(toPascalCase($name));
}

/**
 * @brief Convert a name to snake_case.
 * @param string $name
 * @return string
 */
function toSnakeCase(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9]+/', '_', $name);
    $name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
    return strtolower(trim((string)$name, '_'));
}

/**
 * @brief Return the generated model file extension.
 * @param string $language
 * @return string
 */
function modelExtension(string $language): string {
    return match ($language) {
        'python' => 'py',
        'javascript' => 'js',
        'typescript' => 'ts',
        'csharp' => 'cs',
        'cpp' => 'h',
        'java' => 'java',
        'go' => 'go',
        'ruby' => 'rb',
        'kotlin' => 'kt',
        'swift' => 'swift',
        'rust' => 'rs',
        default => 'php',
    };
}

/**
 * @brief Return the display label for a language.
 * @param string $language
 * @return string
 */
function languageLabel(string $language): string {
    return match ($language) {
        'python' => 'Python',
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'csharp' => 'C#',
        'cpp' => 'C++',
        'java' => 'Java',
        'go' => 'Go',
        'ruby' => 'Ruby',
        'kotlin' => 'Kotlin',
        'swift' => 'Swift',
        'rust' => 'Rust',
        default => 'PHP',
    };
}

/**
 * @brief Map a SQL column type to a generated language type.
 * @param string $sqlType
 * @param string $language
 * @param bool $nullable
 * @return string
 */
function sqlTypeToLanguage(string $sqlType, string $language, bool $nullable = false): string {
    $phpType = sqlTypeToPhp($sqlType);

    return match ($language) {
        'python' => match ($phpType) {
            'int' => $nullable ? 'Optional[int]' : 'int',
            'float' => $nullable ? 'Optional[float]' : 'float',
            'bool' => $nullable ? 'Optional[bool]' : 'bool',
            default => $nullable ? 'Optional[str]' : 'str',
        },
        'javascript' => 'any',
        'typescript' => match ($phpType) {
            'int', 'float' => $nullable ? 'number | null' : 'number',
            'bool' => $nullable ? 'boolean | null' : 'boolean',
            default => $nullable ? 'string | null' : 'string',
        },
        'csharp' => match ($phpType) {
            'int' => $nullable ? 'int?' : 'int',
            'float' => $nullable ? 'double?' : 'double',
            'bool' => $nullable ? 'bool?' : 'bool',
            default => $nullable ? 'string?' : 'string',
        },
        'cpp' => match ($phpType) {
            'int' => $nullable ? 'std::optional<long long>' : 'long long',
            'float' => $nullable ? 'std::optional<double>' : 'double',
            'bool' => $nullable ? 'std::optional<bool>' : 'bool',
            default => $nullable ? 'std::optional<std::string>' : 'std::string',
        },
        'java' => match ($phpType) {
            'int' => $nullable ? 'Integer' : 'int',
            'float' => $nullable ? 'Double' : 'double',
            'bool' => $nullable ? 'Boolean' : 'boolean',
            default => 'String',
        },
        'go' => match ($phpType) {
            'int' => $nullable ? '*int' : 'int',
            'float' => $nullable ? '*float64' : 'float64',
            'bool' => $nullable ? '*bool' : 'bool',
            default => $nullable ? '*string' : 'string',
        },
        'ruby' => 'Object',
        'kotlin' => match ($phpType) {
            'int' => $nullable ? 'Int?' : 'Int',
            'float' => $nullable ? 'Double?' : 'Double',
            'bool' => $nullable ? 'Boolean?' : 'Boolean',
            default => $nullable ? 'String?' : 'String',
        },
        'swift' => match ($phpType) {
            'int' => $nullable ? 'Int?' : 'Int',
            'float' => $nullable ? 'Double?' : 'Double',
            'bool' => $nullable ? 'Bool?' : 'Bool',
            default => $nullable ? 'String?' : 'String',
        },
        'rust' => match ($phpType) {
            'int' => $nullable ? 'Option<i64>' : 'i64',
            'float' => $nullable ? 'Option<f64>' : 'f64',
            'bool' => $nullable ? 'Option<bool>' : 'bool',
            default => $nullable ? 'Option<String>' : 'String',
        },
        default => $nullable ? '?' . $phpType : $phpType,
    };
}

/**
 * @brief Normalize schema columns for model generation.
 * @param array $schema
 * @param array $pk
 * @return array
 */
function normalizedModelColumns(array $schema, array $pk): array {
    $columns = [];
    foreach ($schema as $col) {
        $name = $col['name'];
        $extra = strtolower($col['extra'] ?? '');
        $isAutoIncrement = in_array($name, $pk, true) &&
            (str_contains($extra, 'auto_increment') || str_contains($extra, 'serial'));

        $columns[] = [
            'name' => $name,
            'camel' => toCamelCase($name),
            'pascal' => toPascalCase($name),
            'snake' => toSnakeCase($name),
            'nullable' => (bool)($col['nullable'] ?? false) || $isAutoIncrement,
            'type' => $col['type'],
            'isAutoInc' => $isAutoIncrement,
        ];
    }
    return $columns;
}

/**
 * @brief Generate C++ header and source model files.
 * @param string $table
 * @param array $schema
 * @param array $pk
 * @return array
 */
function generateCppModelFiles(string $table, array $schema, array $pk): array {
    $className = toPascalCase($table);
    $columns = normalizedModelColumns($schema, $pk);
    $pkCol = $pk[0] ?? 'id';
    $fields = '';
    $declarations = '';
    $definitions = '';
    $jsonAssignments = '';

    foreach ($columns as $c) {
        $type = sqlTypeToLanguage($c['type'], 'cpp', $c['nullable']);
        $fields .= "    {$type} {$c['snake']}_{};\n";
        $declarations .= "\n    const {$type}& get{$c['pascal']}() const;\n    void set{$c['pascal']}(const {$type}& value);\n";
        $definitions .= "\nconst {$type}& {$className}::get{$c['pascal']}() const\n{\n    return {$c['snake']}_;\n}\n\nvoid {$className}::set{$c['pascal']}(const {$type}& value)\n{\n    {$c['snake']}_ = value;\n}\n";
        if ($c['nullable']) {
            $jsonAssignments .= "    data[\"{$c['name']}\"] = {$c['snake']}.has_value() ? nlohmann::json(*{$c['snake']}_) : nlohmann::json(nullptr);\n";
        } else {
            $jsonAssignments .= "    data[\"{$c['name']}\"] = {$c['snake']}_;\n";
        }
    }

    $header = "#pragma once\n\n#include <optional>\n#include <string>\n\nclass {$className}\n{\npublic:\n    static constexpr const char* TABLE = \"{$table}\";\n    static constexpr const char* PRIMARY_KEY = \"{$pkCol}\";\n{$declarations}\n    bool writeJsonFile(const std::string& filePath) const;\n\nprivate:\n{$fields}};\n";

    $source = "#include \"{$className}.h\"\n\n#include <fstream>\n#include <nlohmann/json.hpp>\n{$definitions}\nbool {$className}::writeJsonFile(const std::string& filePath) const\n{\n    nlohmann::json data;\n{$jsonAssignments}\n\n    std::ofstream file(filePath);\n    if (!file.is_open()) {\n        return false;\n    }\n\n    file << data.dump(4);\n    return true;\n}\n";

    return [
        "{$className}.h" => $header,
        "{$className}.cpp" => $source,
    ];
}

/**
 * @brief Generate model source code for the selected language.
 * @param string $language
 * @param string $table
 * @param array $schema
 * @param array $pk
 * @param string $namespace
 * @return string
 */
function generateModelForLanguage(string $language, string $table, array $schema, array $pk, string $namespace = ''): string {
    $className = toPascalCase($table);
    $columns = normalizedModelColumns($schema, $pk);
    $pkCol = $pk[0] ?? 'id';
    $label = languageLabel($language);

    if ($language === 'python') {
        $params = $assigns = $properties = $fromRow = $toDict = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $params .= "        {$c['snake']}: {$type} = None,\n";
            $assigns .= "        self._{$c['snake']} = {$c['snake']}\n";
            $properties .= "\n    @property\n    def {$c['snake']}(self) -> {$type}:\n        return self._{$c['snake']}\n\n    @{$c['snake']}.setter\n    def {$c['snake']}(self, value: {$type}) -> None:\n        self._{$c['snake']} = value\n";
            $fromRow .= "            {$c['snake']}=row.get('{$c['name']}'),\n";
            $toDict .= "            '{$c['name']}': self.{$c['snake']},\n";
        }
        return "import json\nfrom typing import Any, Dict, Optional\n\n\nclass {$className}:\n    \"\"\"Structured model for the {$table} table.\"\"\"\n\n    TABLE = '{$table}'\n    PRIMARY_KEY = '{$pkCol}'\n\n    def __init__(\n        self,\n{$params}    ) -> None:\n{$assigns}{$properties}\n    @classmethod\n    def from_row(cls, row: Dict[str, Any]) -> '{$className}':\n        return cls(\n{$fromRow}        )\n\n    def to_dict(self) -> Dict[str, Any]:\n        return {\n{$toDict}        }\n\n    def write_json_file(self, file_path: str) -> None:\n        with open(file_path, 'w', encoding='utf-8') as file:\n            json.dump(self.to_dict(), file, indent=4)\n";
    }

    if ($language === 'typescript') {
        $interface = $fields = $ctor = $accessors = $fromRow = $toObject = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $interface .= "  {$c['camel']}: {$type};\n";
            $fields .= "  private _{$c['camel']}: {$type};\n";
            $ctor .= "    this._{$c['camel']} = data.{$c['camel']};\n";
            $accessors .= "\n  get {$c['camel']}(): {$type} {\n    return this._{$c['camel']};\n  }\n\n  set {$c['camel']}(value: {$type}) {\n    this._{$c['camel']} = value;\n  }\n";
            $fromRow .= "      {$c['camel']}: row['{$c['name']}'],\n";
            $toObject .= "      '{$c['name']}': this.{$c['camel']},\n";
        }
        return "import { writeFileSync } from 'node:fs';\n\nexport interface {$className}Data {\n{$interface}}\n\nexport class {$className} {\n  static readonly table = '{$table}';\n  static readonly primaryKey = '{$pkCol}';\n\n{$fields}\n  constructor(data: {$className}Data) {\n{$ctor}  }\n{$accessors}\n  static fromRow(row: Record<string, any>): {$className} {\n    return new {$className}({\n{$fromRow}    });\n  }\n\n  toObject(): Record<string, any> {\n    return {\n{$toObject}    };\n  }\n\n  writeJsonFile(filePath: string): void {\n    writeFileSync(filePath, JSON.stringify(this.toObject(), null, 2), 'utf8');\n  }\n}\n";
    }

    if ($language === 'javascript') {
        $assigns = $accessors = $fromRow = $toObject = '';
        foreach ($columns as $c) {
            $assigns .= "    this._{$c['camel']} = data.{$c['camel']} ?? null;\n";
            $accessors .= "\n  get {$c['camel']}() {\n    return this._{$c['camel']};\n  }\n\n  set {$c['camel']}(value) {\n    this._{$c['camel']} = value;\n  }\n";
            $fromRow .= "      {$c['camel']}: row['{$c['name']}'],\n";
            $toObject .= "      '{$c['name']}': this.{$c['camel']},\n";
        }
        return "import { writeFileSync } from 'node:fs';\n\nexport class {$className} {\n  static table = '{$table}';\n  static primaryKey = '{$pkCol}';\n\n  constructor(data = {}) {\n{$assigns}  }\n{$accessors}\n  static fromRow(row) {\n    return new {$className}({\n{$fromRow}    });\n  }\n\n  toObject() {\n    return {\n{$toObject}    };\n  }\n\n  writeJsonFile(filePath) {\n    writeFileSync(filePath, JSON.stringify(this.toObject(), null, 2), 'utf8');\n  }\n}\n";
    }

    if ($language === 'csharp') {
        $fields = $methods = $jsonFields = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    private {$type} _{$c['camel']};\n";
            $methods .= "\n    public {$type} Get{$c['pascal']}()\n    {\n        return _{$c['camel']};\n    }\n\n    public void Set{$c['pascal']}({$type} value)\n    {\n        _{$c['camel']} = value;\n    }\n";
            $jsonFields .= "            [\"{$c['name']}\"] = _{$c['camel']},\n";
        }
        $ns = trim($namespace) !== '' ? "namespace " . trim(str_replace('\\', '.', $namespace), '.') . ";\n\n" : '';
        return "using System.Collections.Generic;\nusing System.IO;\nusing System.Text.Json;\n\n{$ns}public class {$className}\n{\n    public const string Table = \"{$table}\";\n    public const string PrimaryKey = \"{$pkCol}\";\n\n{$fields}{$methods}\n    public void WriteJsonFile(string filePath)\n    {\n        var data = new Dictionary<string, object?>\n        {\n{$jsonFields}        };\n\n        var json = JsonSerializer.Serialize(data, new JsonSerializerOptions { WriteIndented = true });\n        File.WriteAllText(filePath, json);\n    }\n}\n";
    }

    if ($language === 'java') {
        $fields = $methods = $jsonPuts = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    private {$type} {$c['camel']};\n";
            $methods .= "\n    public {$type} get{$c['pascal']}() {\n        return {$c['camel']};\n    }\n\n    public void set{$c['pascal']}({$type} {$c['camel']}) {\n        this.{$c['camel']} = {$c['camel']};\n    }\n";
            $jsonPuts .= "        data.put(\"{$c['name']}\", {$c['camel']});\n";
        }
        return "import com.fasterxml.jackson.databind.ObjectMapper;\nimport java.io.File;\nimport java.io.IOException;\nimport java.util.LinkedHashMap;\nimport java.util.Map;\n\npublic class {$className} {\n    public static final String TABLE = \"{$table}\";\n    public static final String PRIMARY_KEY = \"{$pkCol}\";\n\n{$fields}{$methods}\n    public void writeJsonFile(String filePath) throws IOException {\n        Map<String, Object> data = new LinkedHashMap<>();\n{$jsonPuts}\n        new ObjectMapper()\n            .writerWithDefaultPrettyPrinter()\n            .writeValue(new File(filePath), data);\n    }\n}\n";
    }

    if ($language === 'cpp') {
        $fields = $methods = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    {$type} {$c['snake']}_{};\n";
            $methods .= "\n    const {$type}& get{$c['pascal']}() const\n    {\n        return {$c['snake']}_;\n    }\n\n    void set{$c['pascal']}(const {$type}& value)\n    {\n        {$c['snake']}_ = value;\n    }\n";
        }
        return "#pragma once\n\n#include <optional>\n#include <string>\n\nclass {$className}\n{\npublic:\n    static constexpr const char* TABLE = \"{$table}\";\n    static constexpr const char* PRIMARY_KEY = \"{$pkCol}\";\n{$methods}\nprivate:\n{$fields}};\n";
    }

    if ($language === 'go') {
        $fields = $methods = $jsonMap = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    {$c['camel']} {$type}\n";
            $methods .= "\nfunc (m *{$className}) Get{$c['pascal']}() {$type} {\n    return m.{$c['camel']}\n}\n\nfunc (m *{$className}) Set{$c['pascal']}(value {$type}) {\n    m.{$c['camel']} = value\n}\n";
            $jsonMap .= "        \"{$c['name']}\": m.{$c['camel']},\n";
        }
        return "package models\n\nimport (\n    \"encoding/json\"\n    \"os\"\n)\n\nconst {$className}Table = \"{$table}\"\nconst {$className}PrimaryKey = \"{$pkCol}\"\n\ntype {$className} struct {\n{$fields}}\n{$methods}\nfunc (m *{$className}) WriteJSONFile(filePath string) error {\n    data := map[string]interface{}{\n{$jsonMap}    }\n\n    bytes, err := json.MarshalIndent(data, \"\", \"  \")\n    if err != nil {\n        return err\n    }\n\n    return os.WriteFile(filePath, bytes, 0644)\n}\n";
    }

    if ($language === 'ruby') {
        $attrs = implode(', ', array_map(fn($c) => ':' . $c['snake'], $columns));
        $assigns = implode("\n", array_map(fn($c) => "    @{$c['snake']} = attrs[:{$c['snake']}]", $columns));
        $toHash = implode("\n", array_map(fn($c) => "      {$c['name']}: @{$c['snake']},", $columns));
        return "require 'json'\n\nclass {$className}\n  TABLE = '{$table}'\n  PRIMARY_KEY = '{$pkCol}'\n\n  attr_accessor {$attrs}\n\n  def initialize(attrs = {})\n{$assigns}\n  end\n\n  def to_h\n    {\n{$toHash}\n    }\n  end\n\n  def write_json_file(file_path)\n    File.write(file_path, JSON.pretty_generate(to_h))\n  end\nend\n";
    }

    if ($language === 'kotlin') {
        $ctorFields = $methods = $jsonPuts = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $ctorFields .= "    private var {$c['camel']}: {$type},\n";
            $methods .= "\n    fun get{$c['pascal']}(): {$type} {\n        return {$c['camel']}\n    }\n\n    fun set{$c['pascal']}(value: {$type}) {\n        {$c['camel']} = value\n    }\n";
            $jsonPuts .= "            put(\"{$c['name']}\", {$c['camel']})\n";
        }
        return "import java.io.File\nimport org.json.JSONObject\n\nclass {$className}(\n{$ctorFields}\n) {\n{$methods}\n    fun writeJsonFile(filePath: String) {\n        val data = JSONObject().apply {\n{$jsonPuts}        }\n\n        File(filePath).writeText(data.toString(4))\n    }\n\n    companion object {\n        const val TABLE = \"{$table}\"\n        const val PRIMARY_KEY = \"{$pkCol}\"\n    }\n}\n";
    }

    if ($language === 'swift') {
        $fields = $initParams = $initAssigns = $methods = $jsonMap = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    private var {$c['camel']}: {$type}\n";
            $initParams .= "        {$c['camel']}: {$type},\n";
            $initAssigns .= "        self.{$c['camel']} = {$c['camel']}\n";
            $methods .= "\n    func get{$c['pascal']}() -> {$type} {\n        return {$c['camel']}\n    }\n\n    mutating func set{$c['pascal']}(_ value: {$type}) {\n        {$c['camel']} = value\n    }\n";
            $jsonMap .= "            \"{$c['name']}\": {$c['camel']},\n";
        }
        return "import Foundation\n\nstruct {$className} {\n    static let table = \"{$table}\"\n    static let primaryKey = \"{$pkCol}\"\n\n{$fields}\n    init(\n{$initParams}    ) {\n{$initAssigns}    }\n{$methods}\n    func writeJsonFile(filePath: String) throws {\n        let data: [String: Any?] = [\n{$jsonMap}        ]\n        let jsonData = try JSONSerialization.data(withJSONObject: data.compactMapValues { \$0 }, options: [.prettyPrinted])\n        try jsonData.write(to: URL(fileURLWithPath: filePath))\n    }\n}\n";
    }

    if ($language === 'rust') {
        $fields = $methods = $jsonMap = '';
        foreach ($columns as $c) {
            $type = sqlTypeToLanguage($c['type'], $language, $c['nullable']);
            $fields .= "    {$c['snake']}: {$type},\n";
            $methods .= "\n    pub fn get_{$c['snake']}(&self) -> &{$type} {\n        &self.{$c['snake']}\n    }\n\n    pub fn set_{$c['snake']}(&mut self, value: {$type}) {\n        self.{$c['snake']} = value;\n    }\n";
            $jsonMap .= "            \"{$c['name']}\": &self.{$c['snake']},\n";
        }
        return "use std::fs::File;\nuse std::io::Write;\n\n#[derive(Debug, Clone, serde::Serialize, serde::Deserialize)]\npub struct {$className} {\n{$fields}}\n\nimpl {$className} {\n    pub const TABLE: &'static str = \"{$table}\";\n    pub const PRIMARY_KEY: &'static str = \"{$pkCol}\";\n{$methods}\n    pub fn write_json_file(&self, file_path: &str) -> Result<(), Box<dyn std::error::Error>> {\n        let data = serde_json::json!({\n{$jsonMap}        });\n        let mut file = File::create(file_path)?;\n        file.write_all(serde_json::to_string_pretty(&data)?.as_bytes())?;\n        Ok(())\n    }\n}\n";
    }

    return "// {$label} generation is not available.\n";
}

if ($selectedTable && $db && ($_SERVER['REQUEST_METHOD'] === 'POST' || $regenerate)) {
    try {
        $schema = $db->getTableSchema($selectedTable, $dbName);
        $pk     = $db->getPrimaryKey($selectedTable, $dbName);
        $pkCol  = $pk[0] ?? 'id';

        $className = toPascalCase($selectedTable);
        $nsEscaped = rtrim($namespace, '\\');

        if ($language === 'cpp') {
            $generatedFiles = generateCppModelFiles($selectedTable, $schema, $pk);
            $generatedCode = '';
            foreach ($generatedFiles as $filename => $code) {
                $generatedCode .= "// ===== {$filename} =====\n{$code}\n";
            }
        } elseif ($language !== 'php') {
            $generatedCode = generateModelForLanguage($language, $selectedTable, $schema, $pk, $namespace);
        } else {
        // --- Build properties ---
        $properties = [];
        $constructParams = [];
        $constructAssigns = [];
        $gettersSetters = [];
        $fromRowMap = [];
        $toArrayMap = [];
        $fillable = [];

        foreach ($schema as $col) {
            $phpType   = sqlTypeToPhp($col['type']);
            $nullable  = $col['nullable'] ? true : false;
            $propName  = toCamelCase($col['name']);
            $colName   = $col['name'];
            $typeHint  = $nullable ? '?' . $phpType : $phpType;
            $default   = '';

            // Auto-increment PKs are nullable by nature (not set on insert)
            $extra = strtolower($col['extra'] ?? '');
            $isAutoIncrement = in_array($colName, $pk) &&
                (str_contains($extra, 'auto_increment') || str_contains($extra, 'serial'));

            if ($isAutoIncrement) {
                $typeHint = '?int';
                $default  = ' = null';
            } elseif ($nullable) {
                $default = ' = null';
            }

            $properties[] = [
                'name'     => $propName,
                'column'   => $colName,
                'type'     => $typeHint,
                'default'  => $default,
                'phpType'  => $phpType,
                'nullable' => $nullable || $isAutoIncrement,
                'isAutoInc'=> $isAutoIncrement,
            ];

            if (!$isAutoIncrement) {
                $fillable[] = "'{$colName}'";
            }
        }

        // === Generate Code Based on Style ===

        $propLines = '';
        $ctorParams = [];
        $ctorBody = '';
        $getterSetter = '';
        $fromRow = '';
        $toArray = '';

        foreach ($properties as $p) {
            // Property declaration
            $propLines .= "    private {$p['type']} \${$p['name']}{$p['default']};\n";

            // Constructor parameter
            $ctorParams[] = "        {$p['type']} \${$p['name']}{$p['default']}";

            // Constructor body
            $ctorBody .= "        \$this->{$p['name']} = \${$p['name']};\n";

            // Getter
            $ucName = ucfirst($p['name']);
            $getterSetter .= <<<EOG

    public function get{$ucName}(): {$p['type']}
    {
        return \$this->{$p['name']};
    }

EOG;
            // Setter (skip auto-increment)
            if (!$p['isAutoInc']) {
                $getterSetter .= <<<EOS

    public function set{$ucName}({$p['type']} \$value): self
    {
        \$this->{$p['name']} = \$value;
        return \$this;
    }

EOS;
            }

            // fromRow mapping
            $cast = match ($p['phpType']) {
                'int'   => '(int)',
                'float' => '(float)',
                'bool'  => '(bool)',
                default => '(string)',
            };
            if ($p['nullable']) {
                $fromRow .= "            isset(\$row['{$p['column']}']) ? {$cast}\$row['{$p['column']}'] : null,\n";
            } else {
                $fromRow .= "            {$cast}(\$row['{$p['column']}'] ?? ''),\n";
            }

            // toArray mapping
            $toArray .= "            '{$p['column']}' => \$this->{$p['name']},\n";
        }

        $ctorParamsStr = implode(",\n", $ctorParams);
        $fillableStr   = implode(', ', $fillable);

        // Common class body
        $classBody = <<<PHP
    /** @var string Table name */
    public const TABLE = '{$selectedTable}';

    /** @var string Primary key column */
    public const PRIMARY_KEY = '{$pkCol}';

    /** @var array Fillable columns (excludes auto-increment) */
    public const FILLABLE = [{$fillableStr}];

{$propLines}
    /**
     * @brief Constructor.
     */
    public function __construct(
{$ctorParamsStr}
    ) {
{$ctorBody}    }

    /**
     * @brief  Create model from a database row array.
     * @param  array \$row Associative row data.
     * @return self
     */
    public static function fromRow(array \$row): self
    {
        return new self(
{$fromRow}        );
    }

    /**
     * @brief  Convert model to associative array.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
{$toArray}        ];
    }

    /**
     * @brief  Write the current model values to a JSON file.
     * @param  string \$filePath Target JSON file path.
     */
    public function writeJsonFile(string \$filePath): void
    {
        file_put_contents(
            \$filePath,
            json_encode(\$this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @brief  Get only fillable fields as array (for inserts).
     * @return array<string, mixed>
     */
    public function toFillableArray(): array
    {
        return array_intersect_key(\$this->toArray(), array_flip(self::FILLABLE));
    }
{$getterSetter}
PHP;

        // Active Record additions
        $activeRecordMethods = '';
        if ($style === 'activerecord') {
            $activeRecordMethods = <<<'PHP'

    // ======= Active Record Methods =======

    /** @var \PDO|null Shared PDO connection */
    private static ?\PDO $pdo = null;

    /**
     * @brief  Set the shared PDO connection.
     * @param  \PDO $pdo
     */
    public static function setConnection(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * @brief  Find a record by primary key.
     * @param  mixed $id
     * @return self|null
     */
    public static function find(mixed $id): ?self
    {
        $stmt = self::$pdo->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE ' . self::PRIMARY_KEY . ' = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? self::fromRow($row) : null;
    }

    /**
     * @brief  Fetch all records (with optional limit).
     * @param  int $limit
     * @param  int $offset
     * @return self[]
     */
    public static function all(int $limit = 100, int $offset = 0): array
    {
        $stmt = self::$pdo->prepare(
            'SELECT * FROM ' . self::TABLE . ' LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = self::fromRow($row);
        }
        return $results;
    }

    /**
     * @brief  Save (insert or update) this model.
     * @return bool
     */
    public function save(): bool
    {
        $pk = self::PRIMARY_KEY;
        $data = $this->toFillableArray();

        if ($this->{lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $pk))))} !== null) {
            // Update
            $sets = [];
            $vals = [];
            foreach ($data as $col => $val) {
                $sets[] = "{$col} = ?";
                $vals[] = $val;
            }
            $vals[] = $this->toArray()[$pk];
            $sql = 'UPDATE ' . self::TABLE . ' SET ' . implode(', ', $sets) . ' WHERE ' . $pk . ' = ?';
            return self::$pdo->prepare($sql)->execute($vals);
        } else {
            // Insert
            $cols = implode(', ', array_keys($data));
            $phs  = implode(', ', array_fill(0, count($data), '?'));
            $sql  = 'INSERT INTO ' . self::TABLE . " ({$cols}) VALUES ({$phs})";
            $ok   = self::$pdo->prepare($sql)->execute(array_values($data));
            if ($ok) {
                $this->{lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $pk))))} = (int)self::$pdo->lastInsertId();
            }
            return $ok;
        }
    }

    /**
     * @brief  Delete this record.
     * @return bool
     */
    public function destroy(): bool
    {
        $pk  = self::PRIMARY_KEY;
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE ' . $pk . ' = ?';
        return self::$pdo->prepare($sql)->execute([$this->toArray()[$pk]]);
    }

PHP;
        }

        $generatedCode = "<?php\n";
        if ($nsEscaped) {
            $generatedCode .= "namespace {$nsEscaped};\n";
        }
        $generatedCode .= <<<PHP

/**
 * Model class for the "{$selectedTable}" table.
 *
 * Generated by DBExplorer-2 Model Generator.
 *
 * @package {$nsEscaped}
 */
class {$className}
{
{$classBody}{$activeRecordMethods}}

PHP;
        }

    } catch (\Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

if ($db) $db->disconnect();

$pageTitle = 'Model Generator';
require_once __DIR__ . '/includes/header.php';
?>

<div class="breadcrumb">
    <a href="databases.php">Databases</a><span>&rsaquo;</span>
    <a href="tables.php"><?= e(displayDbName()) ?></a><span>&rsaquo;</span>
    <strong>Model Generator</strong>
</div>

<div class="card">
    <h2 class="card-title">Generate Model Class</h2>
    <p class="text-muted mb-2">Select a table and language to generate a model class with table metadata and common row/object mapping helpers.</p>

    <form method="post" class="flex gap-2 flex-wrap items-center mb-2">
        <div class="form-group form-group-flex">
            <label>Table</label>
            <select name="table" required>
                <option value="">-- Select Table --</option>
                <?php foreach ($tables as $t): ?>
                    <option value="<?= e($t) ?>" <?= $selectedTable===$t?'selected':'' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group form-group-fixed-md">
            <label>Namespace</label>
            <input type="text" name="namespace" value="<?= e($namespace) ?>" placeholder="App\Models">
        </div>
        <div class="form-group form-group-fixed">
            <label>Language</label>
            <select name="language">
                <option value="php"        <?= $language==='php'?'selected':'' ?>>PHP</option>
                <option value="python"     <?= $language==='python'?'selected':'' ?>>Python</option>
                <option value="javascript" <?= $language==='javascript'?'selected':'' ?>>JavaScript</option>
                <option value="typescript" <?= $language==='typescript'?'selected':'' ?>>TypeScript</option>
                <option value="csharp"     <?= $language==='csharp'?'selected':'' ?>>C#</option>
                <option value="cpp"        <?= $language==='cpp'?'selected':'' ?>>C++</option>
                <option value="java"       <?= $language==='java'?'selected':'' ?>>Java</option>
                <option value="go"         <?= $language==='go'?'selected':'' ?>>Go</option>
                <option value="ruby"       <?= $language==='ruby'?'selected':'' ?>>Ruby</option>
                <option value="kotlin"     <?= $language==='kotlin'?'selected':'' ?>>Kotlin</option>
                <option value="swift"      <?= $language==='swift'?'selected':'' ?>>Swift</option>
                <option value="rust"       <?= $language==='rust'?'selected':'' ?>>Rust</option>
            </select>
        </div>
        <div class="form-group form-group-fixed">
            <label>Style</label>
            <select name="style">
                <option value="full"         <?= $style==='full'?'selected':'' ?>>Full (Getters/Setters)</option>
                <option value="dataclass"    <?= $style==='dataclass'?'selected':'' ?>>Data Class (Simple)</option>
                <option value="activerecord" <?= $style==='activerecord'?'selected':'' ?>>Active Record</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-form-aligned">Generate</button>
    </form>
</div>

<?php if ($generatedCode):
    $exportFilename = toPascalCase($selectedTable) . '.' . modelExtension($language);
?>
<div class="card">
    <h2 class="card-title">
        Generated <?= e(languageLabel($language)) ?> Model: <?= e(toPascalCase($selectedTable)) ?>
        <span class="title-actions">
            <button class="btn btn-sm btn-secondary copy-btn" data-action="copyCode">Copy Code</button>
            <?php if ($language === 'cpp' && $generatedFiles): ?>
                <?php foreach ($generatedFiles as $filename => $code): ?>
                    <form method="post" class="hidden-form" style="display: inline;">
                        <input type="hidden" name="export_action" value="download">
                        <input type="hidden" name="export_code" value="<?= e($code) ?>">
                        <input type="hidden" name="export_filename" value="<?= e($filename) ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Download <?= str_ends_with($filename, '.h') ? '.h' : '.cpp' ?></button>
                    </form>
                <?php endforeach; ?>
            <?php else: ?>
                <button class="btn btn-sm btn-primary" data-action="submitDownloadForm">Download</button>
            <?php endif; ?>
        </span>
    </h2>
    <p class="text-muted mb-2">
        <?php if ($language === 'cpp' && $generatedFiles): ?>
            <strong>Files:</strong>
            <?php foreach (array_keys($generatedFiles) as $filename): ?>
                <code><?= e($filename) ?></code>
            <?php endforeach; ?>
        <?php else: ?>
            <strong>Filename:</strong> <code><?= e($exportFilename) ?></code>
        <?php endif; ?>
    </p>

    <?php if ($language !== 'cpp' || !$generatedFiles): ?>
    <!-- Download as browser file -->
    <form id="downloadForm" method="post" class="hidden-form">
        <input type="hidden" name="export_action" value="download">
        <input type="hidden" name="export_code" value="<?= e($generatedCode) ?>">
        <input type="hidden" name="export_filename" value="<?= e($exportFilename) ?>">
    </form>
    <?php endif; ?>


    <div class="code-wrap">
        <pre class="code-block" id="generatedCode"><?= e($generatedCode) ?></pre>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
