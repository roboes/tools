# Copilot Studio

## Automatic Language Detection

Automatically detect the language of each user message and set `System.User.Language` so the agent responds in the same language.

### Add Supported Languages

`Settings` → `Languages` → `Add language`.

Add the languages the agent should support, for example:

- English (United States) - `en-US`
- German (Germany) - `de-DE`
- French (France) - `fr-FR`
- Italian (Italy) - `it-IT`
- Spanish (Spain) - `es-ES`

### Create the Language Detection Tool

`Tools` → `Add a tool` → `Prompt`:
- `Name`: `Detect User Language`.
- `Description`: `Detects the primary language of a user message and returns its BCP 47 language tag.`.
- `Additional details`: `When this tool may be used`: `Only when referenced by topics or agents`.
- `Input`: Add a text input named `UserMessage` (`Fill using`: `Dynamically fill with AI`).

- `Instructions`:

```text
Determine the primary natural language of the following user message.

Return the detected language as a BCP 47 language tag.

Examples:
- English → en-US
- German → de-DE
- French → fr-FR
- Italian → it-IT
- Spanish → es-ES
- Portuguese → pt-PT
- Japanese → ja-JP

Rules:
- Return only the BCP 47 language tag and nothing else.
- Do not explain the result.
- Do not translate the message.
- Identify the primary natural language of the user's message.
- If the message is too short or language-neutral to reliably identify the language, return UNKNOWN.
- Names, URLs, email addresses, IDs, numbers, product names, and acronyms alone should return UNKNOWN.
- Standalone acknowledgements and replies such as "ok", "yes", "no", "thanks" or "please" return UNKNOWN.

User message:
[UserMessage]
```

> Insert `[UserMessage]` using the Prompt input control. Do not type it as static text.

Save the tool.

### Create the Language Detector Topic

`Topics` → `Add a topic` → `From blank`:
- `Name`: `Language Detector`.


Open the topic's code editor and replace the generated YAML with (replace `SCHEMA_NAME` with the agent's schema name - Find the agent schema name under: `Settings` → `Advanced` → `Metadata`):

```yaml
kind: AdaptiveDialog
beginDialog:
  kind: OnActivity
  id: main
  type: Message
  actions:
    - kind: BeginDialog
      id: DetectUserLanguage
      input:
        binding:
          UserMessage: =System.Activity.Text

      dialog: SCHEMA_NAME.action.DetectUserLanguage
      output:
        binding:
          predictionOutput: Topic.predictionOutput

    - kind: SetVariable
      id: MapDetectedLanguage
      variable: init:Topic.DetectedLocale
      value: |-
        =Switch(
          Left(Lower(Trim(Topic.predictionOutput.text)), 2),
          "en", Locale.English,
          "de", Locale.German,
          "fr", Locale.French,
          "it", Locale.Italian,
          "es", Locale.Spanish,
          Blank()
        )

    - kind: ConditionGroup
      id: ApplyDetectedLanguage
      conditions:
        - id: ValidLanguage
          condition: =!IsBlank(Topic.DetectedLocale)
          actions:
            - kind: SetVariable
              id: SetDetectedLanguage
              variable: System.User.Language
              value: =Topic.DetectedLocale

inputType: {}
outputType: {}
```

Save the topic.

### Test

Reset the test conversation and enter:

```text
Guten Morgen, wie kann ich Urlaub beantragen?
```

Expected flow:

```text
de-DE → Locale.German → System.User.Language = German
```

The agent should respond in German.

Test another language, for example:

```text
Bonjour, comment puis-je demander mes congés?
```

The agent should switch to French.

#### Optional Debug Output

Temporarily add this to the end of the topic YAML:

```yaml
    - kind: SendActivity
      id: ShowCurrentLanguage
      activity: "Detected: {Topic.predictionOutput.text}; current language: {System.User.Language}"
```

Example output:

```text
Detected: de-DE; current language: German
```

Remove the debug node when testing is complete.

### Add More Languages

When adding another language:

1. Add it under `Settings` → `Languages`.
2. Add its mapping to the `Switch()` in the topic.

For example, Portuguese:

```powerfx
"pt", Locale.Portuguese,
```

The detection Prompt does not need to be changed.

If the detected language is not mapped, `Blank()` is returned and the current `System.User.Language` remains unchanged.
