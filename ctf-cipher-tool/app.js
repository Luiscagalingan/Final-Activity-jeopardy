(() => {
  "use strict";

  const encoder = new TextEncoder();
  const decoder = new TextDecoder("utf-8");
  const strictDecoder = new TextDecoder("utf-8", { fatal: true });

  const operations = [
    {
      id: "caesar",
      name: "Caesar Cipher",
      category: "classic",
      description: "Shifts each letter by a fixed number of alphabet positions.",
      keyLabel: "Shift (0–25)",
      placeholder: "3",
      defaultKey: "3",
      formats: ["Plain text", "Shifted text"],
    },
    {
      id: "rot13",
      name: "ROT13",
      category: "classic",
      description: "Rotates letters by exactly half of the Latin alphabet.",
      formats: ["Plain text", "ROT13 text"],
    },
    {
      id: "rot47",
      name: "ROT47",
      category: "classic",
      description: "Rotates all printable ASCII characters by 47 positions.",
      formats: ["Plain text", "ROT47 text"],
    },
    {
      id: "atbash",
      name: "Atbash Cipher",
      category: "classic",
      description: "Maps A to Z, B to Y, and the rest of the alphabet in reverse.",
      formats: ["Plain text", "Atbash text"],
    },
    {
      id: "vigenere",
      name: "Vigenère Cipher",
      category: "classic",
      description: "Uses a repeating alphabetic keyword to shift letters.",
      keyLabel: "Keyword",
      placeholder: "LEMON",
      defaultKey: "LEMON",
      formats: ["Plain text", "Vigenère text"],
    },
    {
      id: "affine",
      name: "Affine Cipher",
      category: "classic",
      description: "Applies the formula (a × x + b) mod 26 to each letter.",
      keyLabel: "Keys a, b",
      placeholder: "5, 8",
      defaultKey: "5, 8",
      formats: ["Plain text", "Affine text"],
    },
    {
      id: "rail_fence",
      name: "Rail Fence Cipher",
      category: "classic",
      description: "Writes text in a zigzag across multiple rails.",
      keyLabel: "Number of rails",
      placeholder: "3",
      defaultKey: "3",
      formats: ["Plain text", "Rail Fence text"],
    },
    {
      id: "bacon",
      name: "Bacon's Cipher",
      category: "classic",
      description: "Represents letters as five-character A/B groups.",
      formats: ["Plain text", "A/B groups"],
    },
    {
      id: "base64",
      name: "Base64",
      category: "encoding",
      description: "Converts UTF-8 bytes to and from Base64 text.",
      formats: ["Plain text", "Base64"],
    },
    {
      id: "base32",
      name: "Base32",
      category: "encoding",
      description: "Converts UTF-8 bytes using the RFC 4648 Base32 alphabet.",
      formats: ["Plain text", "Base32"],
    },
    {
      id: "base58",
      name: "Base58",
      category: "encoding",
      description: "Encodes bytes with the Bitcoin Base58 alphabet.",
      formats: ["Plain text", "Base58"],
    },
    {
      id: "hex",
      name: "Hexadecimal",
      category: "encoding",
      description: "Converts UTF-8 bytes to and from hexadecimal pairs.",
      formats: ["Plain text", "Hex bytes"],
    },
    {
      id: "binary",
      name: "Binary",
      category: "encoding",
      description: "Converts UTF-8 bytes to and from 8-bit binary groups.",
      formats: ["Plain text", "Binary bytes"],
    },
    {
      id: "octal",
      name: "Octal Bytes",
      category: "encoding",
      description: "Converts UTF-8 bytes to and from three-digit octal values.",
      formats: ["Plain text", "Octal bytes"],
    },
    {
      id: "ascii_decimal",
      name: "ASCII / UTF-8 Decimal",
      category: "encoding",
      description: "Converts text bytes to and from space-separated decimal values.",
      formats: ["Plain text", "Decimal bytes"],
    },
    {
      id: "url",
      name: "URL Encoding",
      category: "encoding",
      description: "Applies or removes percent encoding for URL-safe text.",
      formats: ["Plain text", "Percent encoded"],
    },
    {
      id: "xor_single",
      name: "Single-byte XOR",
      category: "bytes",
      description: "XORs every byte with one numeric key; ciphertext uses hex.",
      keyLabel: "Byte key",
      placeholder: "0x1F or 31",
      defaultKey: "0x1F",
      formats: ["Plain text", "Hex ciphertext"],
    },
    {
      id: "xor_repeating",
      name: "Repeating-key XOR",
      category: "bytes",
      description: "XORs bytes with a repeating text or hexadecimal key.",
      keyLabel: "Text key or hex:…",
      placeholder: "secret or hex:1f2a",
      defaultKey: "secret",
      formats: ["Plain text", "Hex ciphertext"],
    },
    {
      id: "rc4",
      name: "RC4",
      category: "bytes",
      description: "Runs the RC4 stream cipher with a text or hexadecimal key.",
      keyLabel: "Text key or hex:…",
      placeholder: "secret",
      defaultKey: "secret",
      formats: ["Plain text", "Hex ciphertext"],
    },
    {
      id: "xor_bruteforce",
      name: "Single-byte XOR Brute Force",
      category: "bytes",
      description: "Ranks the most readable plaintext candidates across all 256 keys.",
      modes: ["decode"],
      formats: ["Hex ciphertext", "Ranked candidates"],
    },
    {
      id: "morse",
      name: "Morse Code",
      category: "utilities",
      description: "Translates text to dots and dashes; slash separates words.",
      formats: ["Plain text", "Morse code"],
    },
    {
      id: "reverse",
      name: "Reverse Text",
      category: "utilities",
      description: "Reverses the complete string while preserving Unicode characters.",
      formats: ["Text", "Reversed text"],
    },
    {
      id: "unicode_escape",
      name: "Unicode Escapes",
      category: "utilities",
      description: "Converts characters to and from JavaScript-style Unicode escapes.",
      formats: ["Plain text", "Unicode escapes"],
    },
    {
      id: "jwt_inspect",
      name: "JWT Inspector",
      category: "utilities",
      description: "Decodes a JWT header and payload without validating its signature.",
      modes: ["decode"],
      formats: ["JWT token", "Decoded JSON"],
    },
    {
      id: "sha1",
      name: "SHA-1 Hash",
      category: "utilities",
      description: "Calculates a one-way SHA-1 digest in hexadecimal.",
      modes: ["encode"],
      formats: ["Plain text", "SHA-1 digest"],
    },
    {
      id: "sha256",
      name: "SHA-256 Hash",
      category: "utilities",
      description: "Calculates a one-way SHA-256 digest in hexadecimal.",
      modes: ["encode"],
      formats: ["Plain text", "SHA-256 digest"],
    },
    {
      id: "sha512",
      name: "SHA-512 Hash",
      category: "utilities",
      description: "Calculates a one-way SHA-512 digest in hexadecimal.",
      modes: ["encode"],
      formats: ["Plain text", "SHA-512 digest"],
    },
    {
      id: "frequency",
      name: "Letter Frequency",
      category: "utilities",
      description: "Counts and ranks letters to help analyze substitution ciphers.",
      modes: ["decode"],
      formats: ["Cipher text", "Frequency report"],
    },
    {
      id: "auto_detect",
      name: "Auto Detect & Decode",
      category: "utilities",
      description: "Tests common CTF formats and ranks likely readable results.",
      modes: ["decode"],
      formats: ["Unknown text", "Likely decodings"],
    },
  ];

  const morseMap = {
    A: ".-", B: "-...", C: "-.-.", D: "-..", E: ".", F: "..-.", G: "--.", H: "....", I: "..", J: ".---",
    K: "-.-", L: ".-..", M: "--", N: "-.", O: "---", P: ".--.", Q: "--.-", R: ".-.", S: "...", T: "-",
    U: "..-", V: "...-", W: ".--", X: "-..-", Y: "-.--", Z: "--..", 0: "-----", 1: ".----", 2: "..---",
    3: "...--", 4: "....-", 5: ".....", 6: "-....", 7: "--...", 8: "---..", 9: "----.", ".": ".-.-.-",
    ",": "--..--", "?": "..--..", "'": ".----.", "!": "-.-.--", "/": "-..-.", "(": "-.--.", ")": "-.--.-",
    "&": ".-...", ":": "---...", ";": "-.-.-.", "=": "-...-", "+": ".-.-.", "-": "-....-", "_": "..--.-",
    '"': ".-..-.", "$": "...-..-", "@": ".--.-.",
  };
  const reverseMorseMap = Object.fromEntries(Object.entries(morseMap).map(([key, value]) => [value, key]));
  const base32Alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
  const base58Alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

  const elements = {
    tabs: [...document.querySelectorAll(".category-tab")],
    cipherSelect: document.getElementById("cipherSelect"),
    input: document.getElementById("inputText"),
    output: document.getElementById("outputText"),
    keyField: document.getElementById("keyField"),
    keyLabel: document.getElementById("keyLabel"),
    keyInput: document.getElementById("keyInput"),
    encode: document.getElementById("encodeMode"),
    decode: document.getElementById("decodeMode"),
    run: document.getElementById("runButton"),
    swap: document.getElementById("swapButton"),
    copy: document.getElementById("copyButton"),
    clear: document.getElementById("clearButton"),
    example: document.getElementById("exampleButton"),
    inputCount: document.getElementById("inputCount"),
    outputCount: document.getElementById("outputCount"),
    inputFormat: document.getElementById("inputFormat"),
    outputFormat: document.getElementById("outputFormat"),
    runtime: document.getElementById("runtimeStatus"),
    operationName: document.getElementById("operationName"),
    operationDescription: document.getElementById("operationDescription"),
    processOperation: document.getElementById("processOperation"),
    processSteps: document.getElementById("processSteps"),
    error: document.getElementById("errorMessage"),
    quickTools: [...document.querySelectorAll(".quick-tool")],
    toast: document.getElementById("toast"),
    modal: document.getElementById("editorModal"),
    modalTitle: document.getElementById("modalTitle"),
    modalText: document.getElementById("modalText"),
    modalMode: document.getElementById("modalMode"),
    modalApply: document.getElementById("modalApply"),
    inputExpand: document.getElementById("inputExpand"),
    outputExpand: document.getElementById("outputExpand"),
  };

  const state = {
    category: "classic",
    operationId: "caesar",
    mode: "encode",
    runUnlocked: false,
    runToken: 0,
    debounce: null,
    modalTarget: "input",
    keys: {},
  };

  function getOperation(id = state.operationId) {
    return operations.find((item) => item.id === id);
  }

  function utf8ToBytes(text) {
    return encoder.encode(text);
  }

  function bytesToText(bytes, strict = false) {
    return (strict ? strictDecoder : decoder).decode(Uint8Array.from(bytes));
  }

  function bytesToHex(bytes, spaced = true) {
    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join(spaced ? " " : "");
  }

  function parseHex(text) {
    const cleaned = text
      .trim()
      .replace(/0x/gi, "")
      .replace(/[\s,;:_-]+/g, "");
    if (!cleaned) return new Uint8Array();
    if (!/^[0-9a-f]+$/i.test(cleaned)) throw new Error("Hex input may contain only 0–9 and A–F.");
    if (cleaned.length % 2 !== 0) throw new Error("Hex input must contain complete two-digit byte pairs.");
    return Uint8Array.from(cleaned.match(/.{2}/g).map((pair) => Number.parseInt(pair, 16)));
  }

  function bytesToBase64(bytes) {
    let binary = "";
    const chunk = 0x8000;
    for (let i = 0; i < bytes.length; i += chunk) {
      binary += String.fromCharCode(...bytes.slice(i, i + chunk));
    }
    return btoa(binary);
  }

  function base64ToBytes(text) {
    let normalized = text.trim().replace(/\s+/g, "").replace(/-/g, "+").replace(/_/g, "/");
    if (!/^[A-Za-z0-9+/]*={0,2}$/.test(normalized)) throw new Error("The input is not valid Base64 text.");
    normalized += "=".repeat((4 - (normalized.length % 4)) % 4);
    try {
      return Uint8Array.from(atob(normalized), (character) => character.charCodeAt(0));
    } catch {
      throw new Error("The input is not valid Base64 text.");
    }
  }

  function base32Encode(bytes) {
    let bits = 0;
    let value = 0;
    let output = "";
    for (const byte of bytes) {
      value = (value << 8) | byte;
      bits += 8;
      while (bits >= 5) {
        output += base32Alphabet[(value >>> (bits - 5)) & 31];
        bits -= 5;
      }
    }
    if (bits > 0) output += base32Alphabet[(value << (5 - bits)) & 31];
    while (output.length % 8 !== 0) output += "=";
    return output;
  }

  function base32Decode(text) {
    const cleaned = text.toUpperCase().replace(/[\s-]+/g, "").replace(/=+$/, "");
    if (!cleaned || !/^[A-Z2-7]+$/.test(cleaned)) throw new Error("The input is not valid RFC 4648 Base32 text.");
    let bits = 0;
    let value = 0;
    const output = [];
    for (const character of cleaned) {
      value = (value << 5) | base32Alphabet.indexOf(character);
      bits += 5;
      if (bits >= 8) {
        output.push((value >>> (bits - 8)) & 255);
        bits -= 8;
      }
    }
    return Uint8Array.from(output);
  }

  function base58Encode(bytes) {
    if (!bytes.length) return "";
    let value = 0n;
    for (const byte of bytes) value = value * 256n + BigInt(byte);
    let output = "";
    while (value > 0n) {
      const remainder = Number(value % 58n);
      output = base58Alphabet[remainder] + output;
      value /= 58n;
    }
    for (const byte of bytes) {
      if (byte !== 0) break;
      output = "1" + output;
    }
    return output || "1";
  }

  function base58Decode(text) {
    const cleaned = text.trim();
    if (!cleaned || [...cleaned].some((character) => !base58Alphabet.includes(character))) {
      throw new Error("The input contains characters outside the Bitcoin Base58 alphabet.");
    }
    let value = 0n;
    for (const character of cleaned) value = value * 58n + BigInt(base58Alphabet.indexOf(character));
    const output = [];
    while (value > 0n) {
      output.unshift(Number(value % 256n));
      value /= 256n;
    }
    for (const character of cleaned) {
      if (character !== "1") break;
      output.unshift(0);
    }
    return Uint8Array.from(output);
  }

  function rotateLetters(text, amount) {
    const shift = ((amount % 26) + 26) % 26;
    return text.replace(/[A-Za-z]/g, (character) => {
      const base = character <= "Z" ? 65 : 97;
      return String.fromCharCode(((character.charCodeAt(0) - base + shift) % 26) + base);
    });
  }

  function rot47(text) {
    return text.replace(/[!-~]/g, (character) => String.fromCharCode(33 + ((character.charCodeAt(0) - 33 + 47) % 94)));
  }

  function atbash(text) {
    return text.replace(/[A-Za-z]/g, (character) => {
      const base = character <= "Z" ? 65 : 97;
      return String.fromCharCode(base + 25 - (character.charCodeAt(0) - base));
    });
  }

  function vigenere(text, key, decodeMode) {
    const shifts = [...key.toUpperCase()].filter((character) => /[A-Z]/.test(character)).map((character) => character.charCodeAt(0) - 65);
    if (!shifts.length) throw new Error("Enter an alphabetic Vigenère keyword.");
    let index = 0;
    return text.replace(/[A-Za-z]/g, (character) => {
      const base = character <= "Z" ? 65 : 97;
      const shift = shifts[index++ % shifts.length] * (decodeMode ? -1 : 1);
      return String.fromCharCode(((character.charCodeAt(0) - base + shift + 26) % 26) + base);
    });
  }

  function gcd(a, b) {
    while (b) [a, b] = [b, a % b];
    return Math.abs(a);
  }

  function modularInverse(value, modulus) {
    const normalized = ((value % modulus) + modulus) % modulus;
    for (let candidate = 1; candidate < modulus; candidate += 1) {
      if ((normalized * candidate) % modulus === 1) return candidate;
    }
    return null;
  }

  function affine(text, key, decodeMode) {
    const parts = key.split(/[\s,;:]+/).filter(Boolean).map(Number);
    if (parts.length !== 2 || parts.some((part) => !Number.isInteger(part))) throw new Error("Enter Affine keys as two integers, for example 5, 8.");
    let [a, b] = parts;
    if (gcd(a, 26) !== 1) throw new Error("Affine key a must be coprime with 26 (for example 1, 3, 5, 7, 9, 11…).");
    a = ((a % 26) + 26) % 26;
    b = ((b % 26) + 26) % 26;
    const inverse = modularInverse(a, 26);
    return text.replace(/[A-Za-z]/g, (character) => {
      const base = character <= "Z" ? 65 : 97;
      const x = character.charCodeAt(0) - base;
      const result = decodeMode ? (inverse * (x - b + 26)) % 26 : (a * x + b) % 26;
      return String.fromCharCode(base + result);
    });
  }

  function parseRails(key, length) {
    const rails = Number(key);
    if (!Number.isInteger(rails) || rails < 2) throw new Error("Rail Fence requires an integer of 2 or more rails.");
    if (length && rails > length) throw new Error("The number of rails cannot exceed the text length.");
    return rails;
  }

  function railFenceEncode(text, key) {
    const characters = Array.from(text);
    if (characters.length < 2) return text;
    const rails = parseRails(key, characters.length);
    const rows = Array.from({ length: rails }, () => []);
    let row = 0;
    let direction = 1;
    for (const character of characters) {
      rows[row].push(character);
      if (row === 0) direction = 1;
      if (row === rails - 1) direction = -1;
      row += direction;
    }
    return rows.flat().join("");
  }

  function railFenceDecode(text, key) {
    const characters = Array.from(text);
    if (characters.length < 2) return text;
    const rails = parseRails(key, characters.length);
    const pattern = [];
    let row = 0;
    let direction = 1;
    for (let i = 0; i < characters.length; i += 1) {
      pattern.push(row);
      if (row === 0) direction = 1;
      if (row === rails - 1) direction = -1;
      row += direction;
    }
    const counts = Array(rails).fill(0);
    pattern.forEach((rail) => counts[rail] += 1);
    const rows = [];
    let position = 0;
    counts.forEach((count) => {
      rows.push(characters.slice(position, position + count));
      position += count;
    });
    const indices = Array(rails).fill(0);
    return pattern.map((rail) => rows[rail][indices[rail]++]).join("");
  }

  function baconEncode(text) {
    return Array.from(text.toUpperCase(), (character) => {
      if (character === " ") return "/";
      if (!/[A-Z]/.test(character)) return character;
      return (character.charCodeAt(0) - 65).toString(2).padStart(5, "0").replace(/0/g, "A").replace(/1/g, "B");
    }).join(" ");
  }

  function baconDecode(text) {
    const normalized = text.toUpperCase().trim();
    if (!normalized) return "";
    let tokens = normalized.split(/\s+/);
    if (tokens.length === 1 && /^[AB]+$/.test(tokens[0]) && tokens[0].length % 5 === 0) tokens = tokens[0].match(/.{5}/g);
    return tokens.map((token) => {
      if (token === "/") return " ";
      if (!/^[AB]{5}$/.test(token)) return token;
      const value = Number.parseInt(token.replace(/A/g, "0").replace(/B/g, "1"), 2);
      return value < 26 ? String.fromCharCode(65 + value) : "?";
    }).join("");
  }

  function parseSeparatedBytes(text, radix, label, maxValue) {
    const tokens = text.trim().split(/[\s,;:_-]+/).filter(Boolean);
    if (!tokens.length) return new Uint8Array();
    const pattern = radix === 2 ? /^[01]{1,8}$/ : radix === 8 ? /^[0-7]{1,3}$/ : /^\d{1,3}$/;
    const values = tokens.map((token) => {
      if (!pattern.test(token)) throw new Error(`Invalid ${label} byte: ${token}`);
      const value = Number.parseInt(token, radix);
      if (value > maxValue) throw new Error(`${label} byte values must be between 0 and ${maxValue}.`);
      return value;
    });
    return Uint8Array.from(values);
  }

  function parseBinary(text) {
    const trimmed = text.trim();
    if (!trimmed) return new Uint8Array();
    if (/^[01]+$/.test(trimmed) && trimmed.length % 8 === 0) return Uint8Array.from(trimmed.match(/.{8}/g), (group) => Number.parseInt(group, 2));
    return parseSeparatedBytes(trimmed, 2, "binary", 255);
  }

  function parseByteKey(value) {
    const text = value.trim();
    let number;
    if (/^0x[0-9a-f]{1,2}$/i.test(text)) number = Number.parseInt(text.slice(2), 16);
    else if (/^[0-9a-f]{1,2}h$/i.test(text)) number = Number.parseInt(text.slice(0, -1), 16);
    else if (/^\d{1,3}$/.test(text)) number = Number(text);
    else throw new Error("Use a byte key such as 0x1F, 1Fh, or decimal 31.");
    if (number < 0 || number > 255) throw new Error("The XOR key must be between 0 and 255.");
    return number;
  }

  function parseKeyBytes(value) {
    const text = value.trim();
    if (!text) throw new Error("Enter a key.");
    const bytes = text.toLowerCase().startsWith("hex:") ? parseHex(text.slice(4)) : utf8ToBytes(text);
    if (!bytes.length) throw new Error("The key cannot be empty.");
    return bytes;
  }

  function xorBytes(bytes, keyBytes) {
    return Uint8Array.from(bytes, (byte, index) => byte ^ keyBytes[index % keyBytes.length]);
  }

  function rc4Bytes(bytes, key) {
    const stateArray = Uint8Array.from({ length: 256 }, (_, index) => index);
    let j = 0;
    for (let i = 0; i < 256; i += 1) {
      j = (j + stateArray[i] + key[i % key.length]) & 255;
      [stateArray[i], stateArray[j]] = [stateArray[j], stateArray[i]];
    }
    const output = new Uint8Array(bytes.length);
    let i = 0;
    j = 0;
    for (let position = 0; position < bytes.length; position += 1) {
      i = (i + 1) & 255;
      j = (j + stateArray[i]) & 255;
      [stateArray[i], stateArray[j]] = [stateArray[j], stateArray[i]];
      output[position] = bytes[position] ^ stateArray[(stateArray[i] + stateArray[j]) & 255];
    }
    return output;
  }

  function morseEncode(text) {
    return Array.from(text.toUpperCase(), (character) => {
      if (/\s/.test(character)) return "/";
      return morseMap[character] || character;
    }).join(" ");
  }

  function morseDecode(text) {
    return text.trim().split(/\s+/).map((token) => {
      if (token === "/" || token === "|") return " ";
      return reverseMorseMap[token] || "?";
    }).join("");
  }

  function unicodeEscapeEncode(text) {
    return Array.from(text, (character) => {
      const point = character.codePointAt(0);
      return point <= 0xffff ? `\\u${point.toString(16).padStart(4, "0")}` : `\\u{${point.toString(16)}}`;
    }).join("");
  }

  function unicodeEscapeDecode(text) {
    const braceDecoded = text.replace(/\\u\{([0-9a-f]{1,6})\}/gi, (_, hex) => {
      const point = Number.parseInt(hex, 16);
      if (point > 0x10ffff) throw new Error(`Unicode code point U+${hex.toUpperCase()} is out of range.`);
      return String.fromCodePoint(point);
    });
    return braceDecoded
      .replace(/\\u([0-9a-f]{4})/gi, (_, hex) => String.fromCharCode(Number.parseInt(hex, 16)))
      .replace(/\\x([0-9a-f]{2})/gi, (_, hex) => String.fromCharCode(Number.parseInt(hex, 16)));
  }

  function inspectJwt(text) {
    const parts = text.trim().split(".");
    if (parts.length !== 3) throw new Error("A JWT must contain three dot-separated sections.");
    const decodePart = (part) => {
      const decoded = bytesToText(base64ToBytes(part), true);
      try {
        return JSON.parse(decoded);
      } catch {
        return decoded;
      }
    };
    const header = decodePart(parts[0]);
    const payload = decodePart(parts[1]);
    return `HEADER\n${typeof header === "string" ? header : JSON.stringify(header, null, 2)}\n\nPAYLOAD\n${typeof payload === "string" ? payload : JSON.stringify(payload, null, 2)}\n\nSIGNATURE\n${parts[2]}\n\nNote: the signature was not verified.`;
  }

  function paddedBlocks(bytes, blockSize, lengthBytes) {
    const totalLength = Math.ceil((bytes.length + 1 + lengthBytes) / blockSize) * blockSize;
    const padded = new Uint8Array(totalLength);
    padded.set(bytes);
    padded[bytes.length] = 0x80;
    let bitLength = BigInt(bytes.length) * 8n;
    for (let index = 0; index < lengthBytes; index += 1) {
      padded[totalLength - 1 - index] = Number(bitLength & 255n);
      bitLength >>= 8n;
    }
    return padded;
  }

  function sha1Fallback(text) {
    const bytes = paddedBlocks(utf8ToBytes(text), 64, 8);
    let h0 = 0x67452301;
    let h1 = 0xefcdab89;
    let h2 = 0x98badcfe;
    let h3 = 0x10325476;
    let h4 = 0xc3d2e1f0;
    const rotateLeft = (value, bits) => ((value << bits) | (value >>> (32 - bits))) >>> 0;
    for (let offset = 0; offset < bytes.length; offset += 64) {
      const words = new Uint32Array(80);
      for (let i = 0; i < 16; i += 1) {
        const position = offset + i * 4;
        words[i] = ((bytes[position] << 24) | (bytes[position + 1] << 16) | (bytes[position + 2] << 8) | bytes[position + 3]) >>> 0;
      }
      for (let i = 16; i < 80; i += 1) words[i] = rotateLeft(words[i - 3] ^ words[i - 8] ^ words[i - 14] ^ words[i - 16], 1);
      let a = h0;
      let b = h1;
      let c = h2;
      let d = h3;
      let e = h4;
      for (let i = 0; i < 80; i += 1) {
        let f;
        let k;
        if (i < 20) { f = (b & c) | (~b & d); k = 0x5a827999; }
        else if (i < 40) { f = b ^ c ^ d; k = 0x6ed9eba1; }
        else if (i < 60) { f = (b & c) | (b & d) | (c & d); k = 0x8f1bbcdc; }
        else { f = b ^ c ^ d; k = 0xca62c1d6; }
        const temp = (rotateLeft(a, 5) + f + e + k + words[i]) >>> 0;
        e = d;
        d = c;
        c = rotateLeft(b, 30);
        b = a;
        a = temp;
      }
      h0 = (h0 + a) >>> 0;
      h1 = (h1 + b) >>> 0;
      h2 = (h2 + c) >>> 0;
      h3 = (h3 + d) >>> 0;
      h4 = (h4 + e) >>> 0;
    }
    return [h0, h1, h2, h3, h4].map((word) => word.toString(16).padStart(8, "0")).join("");
  }

  const sha256Constants = [
    0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
    0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
    0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
    0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
    0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
    0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
    0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
    0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
  ];

  function sha256Fallback(text) {
    const bytes = paddedBlocks(utf8ToBytes(text), 64, 8);
    const hashes = [0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19];
    const rotateRight = (value, bits) => ((value >>> bits) | (value << (32 - bits))) >>> 0;
    for (let offset = 0; offset < bytes.length; offset += 64) {
      const words = new Uint32Array(64);
      for (let i = 0; i < 16; i += 1) {
        const position = offset + i * 4;
        words[i] = ((bytes[position] << 24) | (bytes[position + 1] << 16) | (bytes[position + 2] << 8) | bytes[position + 3]) >>> 0;
      }
      for (let i = 16; i < 64; i += 1) {
        const s0 = rotateRight(words[i - 15], 7) ^ rotateRight(words[i - 15], 18) ^ (words[i - 15] >>> 3);
        const s1 = rotateRight(words[i - 2], 17) ^ rotateRight(words[i - 2], 19) ^ (words[i - 2] >>> 10);
        words[i] = (words[i - 16] + s0 + words[i - 7] + s1) >>> 0;
      }
      let [a, b, c, d, e, f, g, h] = hashes;
      for (let i = 0; i < 64; i += 1) {
        const sum1 = rotateRight(e, 6) ^ rotateRight(e, 11) ^ rotateRight(e, 25);
        const choose = (e & f) ^ (~e & g);
        const temp1 = (h + sum1 + choose + sha256Constants[i] + words[i]) >>> 0;
        const sum0 = rotateRight(a, 2) ^ rotateRight(a, 13) ^ rotateRight(a, 22);
        const majority = (a & b) ^ (a & c) ^ (b & c);
        const temp2 = (sum0 + majority) >>> 0;
        h = g;
        g = f;
        f = e;
        e = (d + temp1) >>> 0;
        d = c;
        c = b;
        b = a;
        a = (temp1 + temp2) >>> 0;
      }
      [a, b, c, d, e, f, g, h].forEach((value, index) => { hashes[index] = (hashes[index] + value) >>> 0; });
    }
    return hashes.map((word) => word.toString(16).padStart(8, "0")).join("");
  }

  const sha512Constants = [
    "428a2f98d728ae22", "7137449123ef65cd", "b5c0fbcfec4d3b2f", "e9b5dba58189dbbc", "3956c25bf348b538", "59f111f1b605d019", "923f82a4af194f9b", "ab1c5ed5da6d8118",
    "d807aa98a3030242", "12835b0145706fbe", "243185be4ee4b28c", "550c7dc3d5ffb4e2", "72be5d74f27b896f", "80deb1fe3b1696b1", "9bdc06a725c71235", "c19bf174cf692694",
    "e49b69c19ef14ad2", "efbe4786384f25e3", "0fc19dc68b8cd5b5", "240ca1cc77ac9c65", "2de92c6f592b0275", "4a7484aa6ea6e483", "5cb0a9dcbd41fbd4", "76f988da831153b5",
    "983e5152ee66dfab", "a831c66d2db43210", "b00327c898fb213f", "bf597fc7beef0ee4", "c6e00bf33da88fc2", "d5a79147930aa725", "06ca6351e003826f", "142929670a0e6e70",
    "27b70a8546d22ffc", "2e1b21385c26c926", "4d2c6dfc5ac42aed", "53380d139d95b3df", "650a73548baf63de", "766a0abb3c77b2a8", "81c2c92e47edaee6", "92722c851482353b",
    "a2bfe8a14cf10364", "a81a664bbc423001", "c24b8b70d0f89791", "c76c51a30654be30", "d192e819d6ef5218", "d69906245565a910", "f40e35855771202a", "106aa07032bbd1b8",
    "19a4c116b8d2d0c8", "1e376c085141ab53", "2748774cdf8eeb99", "34b0bcb5e19b48a8", "391c0cb3c5c95a63", "4ed8aa4ae3418acb", "5b9cca4f7763e373", "682e6ff3d6b2b8a3",
    "748f82ee5defb2fc", "78a5636f43172f60", "84c87814a1f0ab72", "8cc702081a6439ec", "90befffa23631e28", "a4506cebde82bde9", "bef9a3f7b2c67915", "c67178f2e372532b",
    "ca273eceea26619c", "d186b8c721c0c207", "eada7dd6cde0eb1e", "f57d4f7fee6ed178", "06f067aa72176fba", "0a637dc5a2c898a6", "113f9804bef90dae", "1b710b35131c471b",
    "28db77f523047d84", "32caab7b40c72493", "3c9ebe0a15c9bebc", "431d67c49c100d4c", "4cc5d4becb3e42b6", "597f299cfc657e2a", "5fcb6fab3ad6faec", "6c44198c4a475817",
  ].map((value) => BigInt(`0x${value}`));

  function sha512Fallback(text) {
    const mask = (1n << 64n) - 1n;
    const rotateRight = (value, bits) => ((value >> BigInt(bits)) | (value << BigInt(64 - bits))) & mask;
    const bytes = paddedBlocks(utf8ToBytes(text), 128, 16);
    const hashes = [
      "6a09e667f3bcc908", "bb67ae8584caa73b", "3c6ef372fe94f82b", "a54ff53a5f1d36f1",
      "510e527fade682d1", "9b05688c2b3e6c1f", "1f83d9abfb41bd6b", "5be0cd19137e2179",
    ].map((value) => BigInt(`0x${value}`));
    for (let offset = 0; offset < bytes.length; offset += 128) {
      const words = Array(80).fill(0n);
      for (let i = 0; i < 16; i += 1) {
        let word = 0n;
        for (let j = 0; j < 8; j += 1) word = (word << 8n) | BigInt(bytes[offset + i * 8 + j]);
        words[i] = word;
      }
      for (let i = 16; i < 80; i += 1) {
        const s0 = rotateRight(words[i - 15], 1) ^ rotateRight(words[i - 15], 8) ^ (words[i - 15] >> 7n);
        const s1 = rotateRight(words[i - 2], 19) ^ rotateRight(words[i - 2], 61) ^ (words[i - 2] >> 6n);
        words[i] = (words[i - 16] + s0 + words[i - 7] + s1) & mask;
      }
      let [a, b, c, d, e, f, g, h] = hashes;
      for (let i = 0; i < 80; i += 1) {
        const sum1 = rotateRight(e, 14) ^ rotateRight(e, 18) ^ rotateRight(e, 41);
        const choose = (e & f) ^ ((~e & mask) & g);
        const temp1 = (h + sum1 + choose + sha512Constants[i] + words[i]) & mask;
        const sum0 = rotateRight(a, 28) ^ rotateRight(a, 34) ^ rotateRight(a, 39);
        const majority = (a & b) ^ (a & c) ^ (b & c);
        const temp2 = (sum0 + majority) & mask;
        h = g;
        g = f;
        f = e;
        e = (d + temp1) & mask;
        d = c;
        c = b;
        b = a;
        a = (temp1 + temp2) & mask;
      }
      [a, b, c, d, e, f, g, h].forEach((value, index) => { hashes[index] = (hashes[index] + value) & mask; });
    }
    return hashes.map((word) => word.toString(16).padStart(16, "0")).join("");
  }

  async function hashText(text, algorithm) {
    if (globalThis.crypto?.subtle) {
      const digest = await crypto.subtle.digest(algorithm, utf8ToBytes(text));
      return bytesToHex(new Uint8Array(digest), false);
    }
    if (algorithm === "SHA-1") return sha1Fallback(text);
    if (algorithm === "SHA-256") return sha256Fallback(text);
    if (algorithm === "SHA-512") return sha512Fallback(text);
    throw new Error("This hash algorithm is unavailable.");
  }

  function readabilityScore(text) {
    if (!text) return -1000;
    let score = 0;
    const length = text.length;
    for (const character of text) {
      const code = character.charCodeAt(0);
      if (character === "�" || (code < 9) || (code > 13 && code < 32)) score -= 9;
      else if (/[A-Za-z0-9 _{}\-.,:!?@#$%]/.test(character)) score += 1.5;
      else if (/\s/.test(character)) score += 0.6;
      else score -= 0.2;
    }
    const lower = text.toLowerCase();
    for (const word of ["flag", "ctf", "the", "this", "that", "and", "is", "key", "cipher", "secret", "password"]) {
      if (lower.includes(word)) score += word.length * 3;
    }
    if (/^[\x09\x0a\x0d\x20-\x7e]+$/.test(text)) score += Math.min(length, 40) * 0.35;
    if (/flag\{[^}]+\}/i.test(text)) score += 80;
    return score;
  }

  function xorBruteForce(text) {
    const bytes = parseHex(text);
    if (!bytes.length) return "";
    return Array.from({ length: 256 }, (_, key) => {
      const decoded = bytesToText(Uint8Array.from(bytes, (byte) => byte ^ key));
      return { key, decoded, score: readabilityScore(decoded) };
    })
      .sort((a, b) => b.score - a.score)
      .slice(0, 10)
      .map((candidate, index) => `${String(index + 1).padStart(2, "0")}. key 0x${candidate.key.toString(16).padStart(2, "0").toUpperCase()} (${candidate.key})\n    ${candidate.decoded}`)
      .join("\n\n");
  }

  function frequencyReport(text) {
    const counts = {};
    let total = 0;
    for (const character of text.toUpperCase()) {
      if (!/[A-Z]/.test(character)) continue;
      counts[character] = (counts[character] || 0) + 1;
      total += 1;
    }
    if (!total) throw new Error("No A–Z letters were found in the input.");
    const rows = Object.entries(counts).sort((a, b) => b[1] - a[1]);
    const maximum = rows[0][1];
    return [
      `Letters analyzed: ${total}`,
      `Unique letters: ${rows.length}`,
      "",
      ...rows.map(([letter, count]) => `${letter}  ${String(count).padStart(5)}  ${((count / total) * 100).toFixed(2).padStart(6)}%  ${"█".repeat(Math.max(1, Math.round((count / maximum) * 18)))}`),
    ].join("\n");
  }

  function autoDetect(text) {
    const candidates = [];
    const add = (method, decoded) => {
      if (!decoded || decoded === text || candidates.some((item) => item.decoded === decoded)) return;
      candidates.push({ method, decoded, score: readabilityScore(decoded) });
    };
    const attempt = (method, action) => {
      try { add(method, action()); } catch { /* A failed candidate is expected. */ }
    };

    attempt("Base64", () => bytesToText(base64ToBytes(text), true));
    attempt("Base32", () => bytesToText(base32Decode(text), true));
    attempt("Base58", () => bytesToText(base58Decode(text), true));
    attempt("Hex", () => bytesToText(parseHex(text), true));
    attempt("Binary", () => bytesToText(parseBinary(text), true));
    attempt("URL", () => decodeURIComponent(text));
    attempt("Unicode escapes", () => unicodeEscapeDecode(text));
    attempt("ROT13", () => rotateLetters(text, 13));
    attempt("Atbash", () => atbash(text));
    attempt("Reverse", () => Array.from(text).reverse().join(""));
    for (let shift = 1; shift < 26; shift += 1) add(`Caesar shift ${shift}`, rotateLetters(text, -shift));

    if (!candidates.length) return "No common encoding or cipher candidate could be generated.";
    return candidates
      .sort((a, b) => b.score - a.score)
      .slice(0, 10)
      .map((candidate, index) => `${String(index + 1).padStart(2, "0")}. ${candidate.method}\n    ${candidate.decoded}`)
      .join("\n\n");
  }

  async function transform(operation, text, mode, key) {
    const decodeMode = mode === "decode";
    switch (operation.id) {
      case "caesar": {
        const shift = Number(key);
        if (!Number.isInteger(shift)) throw new Error("The Caesar shift must be an integer.");
        return rotateLetters(text, decodeMode ? -shift : shift);
      }
      case "rot13": return rotateLetters(text, 13);
      case "rot47": return rot47(text);
      case "atbash": return atbash(text);
      case "vigenere": return vigenere(text, key, decodeMode);
      case "affine": return affine(text, key, decodeMode);
      case "rail_fence": return decodeMode ? railFenceDecode(text, key) : railFenceEncode(text, key);
      case "bacon": return decodeMode ? baconDecode(text) : baconEncode(text);
      case "base64": return decodeMode ? bytesToText(base64ToBytes(text), true) : bytesToBase64(utf8ToBytes(text));
      case "base32": return decodeMode ? bytesToText(base32Decode(text), true) : base32Encode(utf8ToBytes(text));
      case "base58": return decodeMode ? bytesToText(base58Decode(text), true) : base58Encode(utf8ToBytes(text));
      case "hex": return decodeMode ? bytesToText(parseHex(text), true) : bytesToHex(utf8ToBytes(text));
      case "binary": return decodeMode ? bytesToText(parseBinary(text), true) : Array.from(utf8ToBytes(text), (byte) => byte.toString(2).padStart(8, "0")).join(" ");
      case "octal": return decodeMode ? bytesToText(parseSeparatedBytes(text, 8, "octal", 255), true) : Array.from(utf8ToBytes(text), (byte) => byte.toString(8).padStart(3, "0")).join(" ");
      case "ascii_decimal": return decodeMode ? bytesToText(parseSeparatedBytes(text, 10, "decimal", 255), true) : Array.from(utf8ToBytes(text)).join(" ");
      case "url": return decodeMode ? decodeURIComponent(text) : encodeURIComponent(text);
      case "xor_single": {
        const keyByte = parseByteKey(key);
        const source = decodeMode ? parseHex(text) : utf8ToBytes(text);
        const result = Uint8Array.from(source, (byte) => byte ^ keyByte);
        return decodeMode ? bytesToText(result) : bytesToHex(result);
      }
      case "xor_repeating": {
        const keyBytes = parseKeyBytes(key);
        const source = decodeMode ? parseHex(text) : utf8ToBytes(text);
        const result = xorBytes(source, keyBytes);
        return decodeMode ? bytesToText(result) : bytesToHex(result);
      }
      case "rc4": {
        const keyBytes = parseKeyBytes(key);
        const source = decodeMode ? parseHex(text) : utf8ToBytes(text);
        const result = rc4Bytes(source, keyBytes);
        return decodeMode ? bytesToText(result) : bytesToHex(result);
      }
      case "xor_bruteforce": return xorBruteForce(text);
      case "morse": return decodeMode ? morseDecode(text) : morseEncode(text);
      case "reverse": return Array.from(text).reverse().join("");
      case "unicode_escape": return decodeMode ? unicodeEscapeDecode(text) : unicodeEscapeEncode(text);
      case "jwt_inspect": return inspectJwt(text);
      case "sha1": return hashText(text, "SHA-1");
      case "sha256": return hashText(text, "SHA-256");
      case "sha512": return hashText(text, "SHA-512");
      case "frequency": return frequencyReport(text);
      case "auto_detect": return autoDetect(text);
      default: throw new Error("This operation is not available.");
    }
  }

  function compactPreview(value, limit = 90) {
    const compact = String(value).replace(/\s+/g, " ").trim();
    if (!compact) return "(empty)";
    return compact.length > limit ? `${compact.slice(0, limit)}…` : compact;
  }

  function formatByteList(bytes, radix = 16, limit = 10) {
    const shown = Array.from(bytes).slice(0, limit).map((byte) => {
      if (radix === 2) return byte.toString(2).padStart(8, "0");
      if (radix === 8) return byte.toString(8).padStart(3, "0");
      if (radix === 10) return String(byte);
      return `0x${byte.toString(16).padStart(2, "0").toUpperCase()}`;
    });
    return `${shown.join(" ")}${bytes.length > limit ? " …" : ""}`;
  }

  function buildLetterMappings(input, output, limit = 9) {
    const source = Array.from(input);
    const result = Array.from(output);
    const mappings = [];
    for (let index = 0; index < Math.min(source.length, result.length); index += 1) {
      if (!/[A-Za-z]/.test(source[index])) continue;
      mappings.push(`${source[index]}→${result[index]}`);
      if (mappings.length >= limit) break;
    }
    return mappings.join(", ") || "Non-letter characters are preserved.";
  }

  function processDetails(operation, input, output, mode, key) {
    const isDecode = mode === "decode";
    const inputBytes = utf8ToBytes(input);
    const steps = [
      {
        title: "Read the input",
        detail: `${input.length.toLocaleString()} characters / ${inputBytes.length.toLocaleString()} UTF-8 bytes. Preview: ${compactPreview(input)}`,
      },
    ];

    const add = (title, detail) => steps.push({ title, detail });
    switch (operation.id) {
      case "caesar": {
        const shift = ((Number(key) % 26) + 26) % 26;
        add(`${isDecode ? "Reverse" : "Apply"} the alphabet shift`, `${isDecode ? "Move each letter backward" : "Move each letter forward"} by ${shift}. ${buildLetterMappings(input, output)}`);
        add("Preserve the flag structure", "Numbers, spaces, underscores, braces, and punctuation are copied without shifting.");
        break;
      }
      case "rot13":
        add("Rotate by half the alphabet", `Every letter moves 13 positions; applying ROT13 twice restores the original. ${buildLetterMappings(input, output)}`);
        break;
      case "rot47":
        add("Rotate printable ASCII", "Characters from ! through ~ move 47 positions across the 94-character printable ASCII range.");
        add("Wrap at the range boundary", `The rotation wraps around when needed. Preview mapping: ${buildLetterMappings(input, output)}`);
        break;
      case "atbash":
        add("Mirror the alphabet", `A↔Z, B↔Y, C↔X, and so on. ${buildLetterMappings(input, output)}`);
        break;
      case "vigenere": {
        const keyword = key.toUpperCase().replace(/[^A-Z]/g, "");
        add("Repeat the keyword", `Keyword “${keyword}” is repeated across alphabetic characters; punctuation does not consume a key letter.`);
        add(`${isDecode ? "Subtract" : "Add"} key shifts`, `${isDecode ? "Cipher letters move backward" : "Plain letters move forward"} using A=0 through Z=25. ${buildLetterMappings(input, output)}`);
        break;
      }
      case "affine": {
        const [a, b] = key.split(/[\s,;:]+/).filter(Boolean);
        add(`${isDecode ? "Invert" : "Apply"} the Affine formula`, isDecode ? `Use the modular inverse of a=${a}, then calculate a⁻¹(x−${b}) mod 26.` : `Convert letters to 0–25 and calculate (${a}×x+${b}) mod 26.`);
        add("Convert numbers back to letters", buildLetterMappings(input, output));
        break;
      }
      case "rail_fence":
        add(`${isDecode ? "Rebuild" : "Create"} the zigzag`, `${isDecode ? "Mark the rail pattern, refill each rail, then read the zigzag." : `Write characters diagonally across ${key} rails, reversing direction at the top and bottom.`}`);
        add(isDecode ? "Read in travel order" : "Read rail by rail", `Result preview: ${compactPreview(output)}`);
        break;
      case "bacon":
        add(isDecode ? "Split A/B groups" : "Convert letters to numbers", isDecode ? "Read each five-symbol A/B group as a five-bit binary value." : "Map A–Z to values 0–25.");
        add(isDecode ? "Map values to letters" : "Write five A/B symbols", isDecode ? `Decoded preview: ${compactPreview(output)}` : `A represents 0 and B represents 1. Result: ${compactPreview(output)}`);
        break;
      case "base64":
        add(isDecode ? "Read Base64 symbols" : "Convert text to UTF-8 bytes", isDecode ? "Each Base64 symbol contributes six bits; padding (=) marks the final incomplete group." : `First bytes: ${formatByteList(inputBytes)}`);
        add(isDecode ? "Rebuild 8-bit bytes" : "Regroup bits into sixes", isDecode ? `The recovered bytes are decoded as UTF-8. Result: ${compactPreview(output)}` : "Every six-bit value is mapped to the Base64 alphabet; = padding is added when required.");
        break;
      case "base32":
        add(isDecode ? "Read Base32 symbols" : "Convert input to bytes", isDecode ? "A–Z and 2–7 represent five-bit values." : `UTF-8 bytes: ${formatByteList(inputBytes)}`);
        add(isDecode ? "Rebuild bytes" : "Regroup into five-bit values", isDecode ? `Eight-bit bytes are reconstructed and decoded as UTF-8.` : "Five-bit values map to the RFC 4648 Base32 alphabet; = supplies padding.");
        break;
      case "base58":
        add(isDecode ? "Convert Base58 digits" : "Interpret bytes as one integer", isDecode ? "Each character is replaced by its value in the Bitcoin Base58 alphabet." : `Input bytes (${inputBytes.length}): ${formatByteList(inputBytes)}`);
        add(isDecode ? "Rebuild the original bytes" : "Repeatedly divide by 58", isDecode ? "The resulting integer is split into base-256 bytes and decoded as UTF-8." : "Remainders select Base58 characters; leading zero bytes become the character 1.");
        break;
      case "hex": {
        const sourceBytes = isDecode ? parseHex(input) : inputBytes;
        add(isDecode ? "Parse hexadecimal pairs" : "Convert text to UTF-8 bytes", isDecode ? `Read ${sourceBytes.length} byte pairs: ${formatByteList(sourceBytes)}` : `Bytes: ${formatByteList(sourceBytes)}`);
        add(isDecode ? "Decode the recovered bytes" : "Write each byte in base 16", isDecode ? `UTF-8 result: ${compactPreview(output)}` : "Every byte becomes two hexadecimal digits from 00 through FF.");
        break;
      }
      case "binary": {
        const sourceBytes = isDecode ? parseBinary(input) : inputBytes;
        add(isDecode ? "Parse 8-bit groups" : "Convert text to UTF-8 bytes", `Bytes: ${formatByteList(sourceBytes)}`);
        add(isDecode ? "Decode bytes as UTF-8" : "Write each byte in binary", isDecode ? `Text result: ${compactPreview(output)}` : `Binary groups: ${formatByteList(sourceBytes, 2, 6)}`);
        break;
      }
      case "octal":
        add(isDecode ? "Parse octal byte values" : "Convert input to UTF-8 bytes", isDecode ? "Each 000–377 group becomes one byte." : `Bytes: ${formatByteList(inputBytes)}`);
        add(isDecode ? "Decode the byte sequence" : "Convert every byte to base 8", `Result preview: ${compactPreview(output)}`);
        break;
      case "ascii_decimal":
        add(isDecode ? "Parse decimal byte values" : "Convert input to UTF-8 bytes", isDecode ? "Each number from 0–255 becomes one byte." : `Decimal values: ${formatByteList(inputBytes, 10)}`);
        add(isDecode ? "Decode bytes as UTF-8" : "Join the numbers with spaces", `Result preview: ${compactPreview(output)}`);
        break;
      case "url":
        add(isDecode ? "Find percent-encoded sequences" : "Identify URL-unsafe characters", isDecode ? "Each %HH sequence is converted back to its byte value." : "Reserved characters and UTF-8 bytes are represented using %HH sequences.");
        add(isDecode ? "Reconstruct readable text" : "Keep safe characters unchanged", `Result preview: ${compactPreview(output)}`);
        break;
      case "xor_single": {
        const keyByte = parseByteKey(key);
        const source = isDecode ? parseHex(input) : inputBytes;
        const examples = Array.from(source).slice(0, 7).map((byte) => `0x${byte.toString(16).padStart(2, "0").toUpperCase()}⊕0x${keyByte.toString(16).padStart(2, "0").toUpperCase()}=0x${(byte ^ keyByte).toString(16).padStart(2, "0").toUpperCase()}`);
        add("Prepare the byte key", `Key ${key} equals decimal ${keyByte} / hex 0x${keyByte.toString(16).padStart(2, "0").toUpperCase()}.`);
        add("XOR each byte", `${examples.join(", ")}${source.length > 7 ? " …" : ""}`);
        break;
      }
      case "xor_repeating": {
        const keyBytes = parseKeyBytes(key);
        const source = isDecode ? parseHex(input) : inputBytes;
        const examples = Array.from(source).slice(0, 6).map((byte, index) => `0x${byte.toString(16).padStart(2, "0")}⊕0x${keyBytes[index % keyBytes.length].toString(16).padStart(2, "0")}`);
        add("Repeat the key bytes", `Key length: ${keyBytes.length} bytes. ${formatByteList(keyBytes)}`);
        add("XOR input and key positions", `${examples.join(", ")}${source.length > 6 ? " …" : ""}; the same operation decrypts the ciphertext.`);
        break;
      }
      case "rc4":
        add("Initialize the RC4 state", `The key (${parseKeyBytes(key).length} bytes) shuffles a 256-byte state array using the key-scheduling algorithm.`);
        add("Generate and XOR the keystream", `${isDecode ? "Cipher bytes" : "Plain bytes"} are XORed with the RC4 pseudo-random byte stream. The result is ${isDecode ? "decoded text" : "formatted as hex"}.`);
        break;
      case "xor_bruteforce":
        add("Try every one-byte key", `The tool tests all 256 values from 0x00 through 0xFF against ${parseHex(input).length} ciphertext bytes.`);
        add("Score readable candidates", "Printable characters, common English words, and FLAG{…} patterns receive higher scores; the best ten results are listed.");
        break;
      case "morse":
        add(isDecode ? "Split Morse tokens" : "Look up each character", isDecode ? "Spaces divide symbols and / divides words." : "Letters, digits, and supported punctuation are mapped to dots and dashes.");
        add(isDecode ? "Map signals back to text" : "Join signals with spaces", `Result preview: ${compactPreview(output)}`);
        break;
      case "reverse":
        add("Read from the opposite end", "Unicode characters are collected safely, then their complete order is reversed.");
        break;
      case "unicode_escape":
        add(isDecode ? "Find escape sequences" : "Read Unicode code points", isDecode ? "\\uXXXX, \\u{XXXXX}, and \\xXX sequences are recognized." : "Each character is converted to its Unicode numeric value.");
        add(isDecode ? "Convert values to characters" : "Format the escape text", `Result preview: ${compactPreview(output)}`);
        break;
      case "jwt_inspect":
        add("Split the token", "A JWT has header.payload.signature sections separated by periods.");
        add("Decode the first two sections", "Base64URL bytes are converted to UTF-8 and formatted as JSON. The signature is displayed but not verified.");
        break;
      case "sha1":
      case "sha256":
      case "sha512":
        add("Convert input to message blocks", `The ${operation.name.replace(" Hash", "")} algorithm pads ${inputBytes.length} input bytes into fixed-size blocks.`);
        add("Run the compression rounds", "Bit rotations, additions, and Boolean functions update the internal hash state for every block.");
        add("Write the one-way digest", "The hexadecimal digest is a fingerprint; it cannot be directly decrypted back to the input.");
        break;
      case "frequency":
        add("Count A–Z letters", "Case is ignored; spaces, numbers, and punctuation are excluded from the letter total.");
        add("Rank by occurrence", "Counts and percentages are sorted from most frequent to least frequent for substitution analysis.");
        break;
      case "auto_detect": {
        const count = (output.match(/^\d{2}\./gm) || []).length;
        add("Test common formats", "The tool attempts Base64, Base32, Base58, Hex, Binary, URL, Unicode escapes, ROT13, Atbash, Reverse, and every Caesar shift.");
        add("Score and rank results", `${count} likely candidates are shown. Printable text, English patterns, and FLAG{…} structures receive higher scores.`);
        break;
      }
      default:
        add("Apply the selected operation", operation.description);
    }

    const outputFormat = operation.formats?.[1] || "result";
    add("Produce the final answer", `${output.length.toLocaleString()} characters in ${outputFormat} format. Preview: ${compactPreview(output)}`);
    return steps;
  }

  function renderProcess(operation, input = "", output = "", errorMessage = "") {
    elements.processOperation.textContent = operation.name.replace(" Cipher", "").replace(" Hash", "");
    elements.processSteps.replaceChildren();
    let steps;
    if (errorMessage) {
      steps = [
        { title: "Input validation stopped", detail: errorMessage },
        { title: "Correct the input", detail: "The final answer will appear automatically after the value, format, or key becomes valid." },
      ];
    } else if (!input) {
      steps = [
        { title: "Enter your input", detail: `Select ${operation.name}, choose Encode or Decode, then start typing.` },
        { title: "Follow the transformation", detail: "This panel will explain the actual key, byte conversion, and algorithm steps." },
        { title: "Read the final answer", detail: "The completed result appears in the panel directly below this explanation." },
      ];
    } else {
      steps = processDetails(operation, input, output, state.mode, elements.keyInput.value);
    }
    const fragment = document.createDocumentFragment();
    steps.forEach((step, index) => {
      const item = document.createElement("li");
      const number = document.createElement("span");
      number.className = "step-number";
      number.textContent = String(index + 1);
      const copy = document.createElement("div");
      const title = document.createElement("strong");
      title.textContent = step.title;
      const detail = document.createElement("p");
      detail.textContent = step.detail;
      copy.append(title, detail);
      item.append(number, copy);
      fragment.append(item);
    });
    elements.processSteps.append(fragment);
  }

  function setRuntime(message, type = "") {
    elements.runtime.textContent = message;
    elements.runtime.className = type;
  }

  function setError(message = "") {
    elements.error.hidden = !message;
    elements.error.textContent = message;
  }

  function updateCounts() {
    elements.inputCount.textContent = `${elements.input.value.length.toLocaleString()} character${elements.input.value.length === 1 ? "" : "s"}`;
    elements.outputCount.textContent = `${elements.output.value.length.toLocaleString()} character${elements.output.value.length === 1 ? "" : "s"}`;
  }

  function refreshOptions(category, preferredId) {
    const available = operations.filter((operation) => operation.category === category);
    elements.cipherSelect.replaceChildren(...available.map((operation) => {
      const option = document.createElement("option");
      option.value = operation.id;
      option.textContent = operation.name;
      return option;
    }));
    state.operationId = available.some((operation) => operation.id === preferredId) ? preferredId : available[0].id;
    elements.cipherSelect.value = state.operationId;
  }

  function updateModeButtons(operation) {
    const modes = operation.modes || ["encode", "decode"];
    elements.encode.disabled = !modes.includes("encode");
    elements.decode.disabled = !modes.includes("decode");
    if (!modes.includes(state.mode)) state.mode = modes[0];
    elements.encode.classList.toggle("active", state.mode === "encode");
    elements.decode.classList.toggle("active", state.mode === "decode");
    elements.encode.setAttribute("aria-pressed", String(state.mode === "encode"));
    elements.decode.setAttribute("aria-pressed", String(state.mode === "decode"));
    elements.swap.disabled = modes.length !== 2;
    elements.swap.title = modes.length !== 2 ? "This one-way operation cannot be swapped" : "Swap input and output";
  }

  function updateOperationUI(run = true) {
    const operation = getOperation();
    updateModeButtons(operation);
    elements.operationName.textContent = operation.name;
    elements.operationDescription.textContent = operation.description;
    elements.keyField.classList.toggle("hidden", !operation.keyLabel);
    if (operation.keyLabel) {
      elements.keyLabel.textContent = operation.keyLabel;
      elements.keyInput.placeholder = operation.placeholder || "Enter key";
      elements.keyInput.value = state.keys[operation.id] ?? operation.defaultKey ?? "";
    }
    const [plainFormat, encodedFormat] = operation.formats || ["Input", "Output"];
    const oneWayOperation = (operation.modes || []).length === 1;
    elements.inputFormat.textContent = oneWayOperation || state.mode === "encode" ? plainFormat : encodedFormat;
    elements.outputFormat.textContent = oneWayOperation || state.mode === "encode" ? encodedFormat : plainFormat;
    elements.quickTools.forEach((button) => button.classList.toggle("selected", button.dataset.operation === operation.id));
    if (!elements.input.value) renderProcess(operation);
    if (run) scheduleRun(0);
  }

  function switchCategory(category, preferredId) {
    state.category = category;
    elements.tabs.forEach((tab) => {
      const active = tab.dataset.category === category;
      tab.classList.toggle("active", active);
      tab.setAttribute("aria-selected", String(active));
    });
    refreshOptions(category, preferredId);
    updateOperationUI();
  }

  function selectOperation(id) {
    const operation = getOperation(id);
    if (!operation) return;
    switchCategory(operation.category, id);
    document.querySelector(".category-tabs").scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function setMode(mode) {
    const operation = getOperation();
    const modes = operation.modes || ["encode", "decode"];
    if (!modes.includes(mode)) return;
    state.mode = mode;
    updateOperationUI();
  }

  async function runTransform(manual = false) {
    clearTimeout(state.debounce);
    const token = ++state.runToken;
    const operation = getOperation();
    const input = elements.input.value;
    const key = elements.keyInput.value;
    updateCounts();
    setError();
    if (!state.runUnlocked) {
      elements.output.value = "";
      hiddenFlagTrigger.hidden = true;
      hiddenFlagAnswer.hidden = true;
      setRuntime("Confirm How It Works first");
      return;
    }
    if (!input) {
      elements.output.value = "";
      hiddenFlagTrigger.hidden = true;
      hiddenFlagAnswer.hidden = true;
      renderProcess(operation);
      updateCounts();
      setRuntime("Ready");
      return;
    }
    setRuntime("Processing…", "processing");
    if (manual) elements.run.style.filter = "brightness(1.15)";
    try {
      const result = await transform(operation, input, state.mode, key);
      if (token !== state.runToken) return;
      renderProcess(operation, input, result);
      elements.output.value = result;
      // The camouflaged flag only becomes available after the player has
      // entered cipher text and successfully processed it.
      if (manual) {
        placeHiddenFlag();
        hiddenFlagTrigger.hidden = false;
      }
      elements.output.classList.remove("result-flash");
      requestAnimationFrame(() => elements.output.classList.add("result-flash"));
      updateCounts();
      setRuntime("Updated", "success");
      setTimeout(() => {
        if (token === state.runToken) setRuntime("Ready");
      }, 1000);
    } catch (error) {
      if (token !== state.runToken) return;
      elements.output.value = "";
      renderProcess(operation, input, "", error instanceof Error ? error.message : "The operation could not be completed.");
      updateCounts();
      setRuntime("Input error", "error");
      setError(error instanceof Error ? error.message : "The operation could not be completed.");
    } finally {
      elements.run.style.filter = "";
    }
  }

  function scheduleRun(delay = 130) {
    clearTimeout(state.debounce);
    state.debounce = setTimeout(() => runTransform(false), delay);
  }

  function showToast(message) {
    elements.toast.textContent = message;
    elements.toast.classList.add("show");
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => elements.toast.classList.remove("show"), 1800);
  }

  async function copyOutput() {
    if (!elements.output.value) return showToast("Nothing to copy yet.");
    try {
      await navigator.clipboard.writeText(elements.output.value);
    } catch {
      elements.output.focus();
      elements.output.select();
      document.execCommand("copy");
    }
    const original = elements.copy.innerHTML;
    elements.copy.textContent = "Copied";
    showToast("Output copied to clipboard.");
    setTimeout(() => { elements.copy.innerHTML = original; }, 1200);
  }

  function swapValues() {
    if (!elements.output.value) return showToast("Run an operation before swapping.");
    const previousOutput = elements.output.value;
    elements.input.value = previousOutput;
    const operation = getOperation();
    const modes = operation.modes || ["encode", "decode"];
    if (modes.length === 2) state.mode = state.mode === "encode" ? "decode" : "encode";
    updateOperationUI();
    elements.input.focus();
  }

  function clearAll() {
    elements.input.value = "";
    elements.output.value = "";
    hiddenFlagTrigger.hidden = true;
    hiddenFlagAnswer.hidden = true;
    setError();
    setRuntime("Ready");
    renderProcess(getOperation());
    updateCounts();
    elements.input.focus();
  }

  function exampleFor(operationId) {
    const examples = {
      caesar: { input: "FLAG{caesar_shift}", key: "3", mode: "encode" },
      rot13: { input: "FLAG{rot13_is_just_a_tool}", mode: "encode" },
      rot47: { input: "FLAG{printable_ascii}", mode: "encode" },
      atbash: { input: "FLAG{mirror_the_alphabet}", mode: "encode" },
      vigenere: { input: "FLAG{keyword_cipher}", key: "LEMON", mode: "encode" },
      affine: { input: "FLAG{affine_math}", key: "5, 8", mode: "encode" },
      rail_fence: { input: "FLAG{zigzag_rails}", key: "3", mode: "encode" },
      bacon: { input: "FLAG BACON", mode: "encode" },
      base64: { input: "FLAG{base64_is_encoding}", mode: "encode" },
      base32: { input: "FLAG{base32}", mode: "encode" },
      base58: { input: "FLAG{base58}", mode: "encode" },
      hex: { input: "FLAG{hex_bytes}", mode: "encode" },
      binary: { input: "FLAG{binary}", mode: "encode" },
      octal: { input: "FLAG{octal}", mode: "encode" },
      ascii_decimal: { input: "FLAG{decimal}", mode: "encode" },
      url: { input: "FLAG{spaces & symbols!}", mode: "encode" },
      xor_single: { input: "FLAG{x0r_is_fun}", key: "0x1F", mode: "encode" },
      xor_repeating: { input: "FLAG{repeating_key}", key: "secret", mode: "encode" },
      rc4: { input: "FLAG{stream_cipher}", key: "secret", mode: "encode" },
      xor_bruteforce: { input: "59 53 5e 58 64 67 73 70 78 62", mode: "decode" },
      morse: { input: "FLAG MORSE", mode: "encode" },
      reverse: { input: "}desrever{GALF", mode: "decode" },
      unicode_escape: { input: "FLAG{unicode_✓}", mode: "encode" },
      jwt_inspect: { input: "eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJmbGFnIjoiRkxBR3tqd3RfaW5zcGVjdGVkfSJ9.", mode: "decode" },
      sha1: { input: "FLAG{hash_me}", mode: "encode" },
      sha256: { input: "FLAG{hash_me}", mode: "encode" },
      sha512: { input: "FLAG{hash_me}", mode: "encode" },
      frequency: { input: "GSRH RH Z HVXIVG NVHHZTV", mode: "decode" },
      auto_detect: { input: "RkxBR3thdXRvX2RldGVjdGVkfQ==", mode: "decode" },
    };
    return examples[operationId] || { input: "FLAG{cipher_challenge}", mode: "encode" };
  }

  function loadExample() {
    const example = exampleFor(state.operationId);
    state.mode = example.mode;
    if (typeof example.key === "string") {
      state.keys[state.operationId] = example.key;
      elements.keyInput.value = example.key;
    }
    elements.input.value = example.input;
    updateOperationUI();
    showToast(`Loaded a ${getOperation().name} example.`);
  }

  function openModal(target) {
    state.modalTarget = target;
    const isInput = target === "input";
    elements.modalTitle.textContent = isInput ? "Expanded input" : "Expanded output";
    elements.modalMode.textContent = isInput ? "Editing input" : "Output preview (read only)";
    elements.modalText.value = isInput ? elements.input.value : elements.output.value;
    elements.modalText.readOnly = !isInput;
    elements.modalApply.hidden = !isInput;
    elements.modal.hidden = false;
    document.body.style.overflow = "hidden";
    setTimeout(() => elements.modalText.focus(), 0);
  }

  function closeModal(apply = false) {
    if (apply && state.modalTarget === "input") {
      elements.input.value = elements.modalText.value;
      scheduleRun(0);
    }
    elements.modal.hidden = true;
    document.body.style.overflow = "";
  }

  elements.tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => switchCategory(tab.dataset.category));
    tab.addEventListener("keydown", (event) => {
      if (!["ArrowLeft", "ArrowRight"].includes(event.key)) return;
      event.preventDefault();
      const nextIndex = (index + (event.key === "ArrowRight" ? 1 : -1) + elements.tabs.length) % elements.tabs.length;
      elements.tabs[nextIndex].focus();
      elements.tabs[nextIndex].click();
    });
  });

  elements.cipherSelect.addEventListener("change", () => {
    state.operationId = elements.cipherSelect.value;
    updateOperationUI();
  });
  elements.input.addEventListener("input", () => scheduleRun());
  elements.keyInput.addEventListener("input", () => {
    state.keys[state.operationId] = elements.keyInput.value;
    scheduleRun();
  });
  elements.encode.addEventListener("click", () => setMode("encode"));
  elements.decode.addEventListener("click", () => setMode("decode"));
  elements.run.addEventListener("click", () => runTransform(true));
  elements.swap.addEventListener("click", swapValues);
  elements.copy.addEventListener("click", copyOutput);
  elements.clear.addEventListener("click", clearAll);
  elements.example.addEventListener("click", loadExample);
  elements.quickTools.forEach((button) => button.addEventListener("click", () => selectOperation(button.dataset.operation)));
  elements.inputExpand.addEventListener("click", () => openModal("input"));
  elements.outputExpand.addEventListener("click", () => openModal("output"));
  elements.modalApply.addEventListener("click", () => closeModal(true));
  document.querySelectorAll("[data-close-modal]").forEach((button) => button.addEventListener("click", () => closeModal(false)));

  const hiddenFlagTrigger = document.getElementById("hiddenFlagTrigger");
  const hiddenFlagAnswer = document.getElementById("hiddenFlagAnswer");
  const hiddenFlagValue = document.getElementById("hiddenFlagValue");
  const closeHiddenFlag = document.getElementById("closeHiddenFlag");
  const understandButton = document.getElementById("understandButton");

  function unlockRun() {
    state.runUnlocked = true;
    elements.run.hidden = false;
    elements.run.disabled = false;
    elements.run.title = "Run the selected cipher operation";
    understandButton.textContent = "Ready";
    understandButton.disabled = true;
  }

  async function askUnderstanding() {
    if (typeof Swal === "undefined") {
      if (window.confirm("Gets mo na ba kung paano gumagana ang process?")) {
        if (window.confirm("Sure ka na ba talaga?")) unlockRun();
      }
      return;
    }

    const firstAnswer = await Swal.fire({
      title: "Gets mo?",
      text: "Naintindihan mo na ba kung paano gumagana ang cipher process?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Oo",
      cancelButtonText: "Hindi",
      confirmButtonColor: "#2563eb",
      cancelButtonColor: "#64748b",
      background: "#0d1733",
      color: "#e5edff"
    });
    if (!firstAnswer.isConfirmed) return;

    const secondAnswer = await Swal.fire({
      title: "Sure ka na ba talaga?",
      text: "Kapag pinindot mo ang Oo, magiging available na ang Run button.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Oo",
      cancelButtonText: "Hindi",
      confirmButtonColor: "#f59e0b",
      cancelButtonColor: "#64748b",
      background: "#0d1733",
      color: "#e5edff"
    });
    if (secondAnswer.isConfirmed) {
      unlockRun();
    }
  }

  understandButton.addEventListener("click", askUnderstanding);
  function knownCtfAnswer(input) {
    const normalized = input.toLowerCase().replace(/\s+/g, " ").trim();
    const compact = normalized.replace(/\s+/g, "");
    const knownChallenges = [
      { matches: ["iodj{flskhu_pdvwhu}"], answer: "FLAG{CIPHER_MASTER}" },
      { matches: ["rkxbr3terunprevftuvfuExfqvnffq==".toLowerCase()], answer: "FLAG{DECODE_ME_PLEASE}" },
      { matches: ["464c41477b6865785f6465636f6465725f77697a6172647d"], answer: "FLAG{hex_decoder_wizard}" },
      { matches: ["827ccb0eea8a706c4c34a16891f84e7b"], answer: "FLAG{12345}" },
      { matches: ["0100011001001100010000010100011101111011011000100110100101101110011000010111001001111001010111110110001001101111011100110111001101111101"], answer: "FLAG{binary_boss}" },
      { matches: ["forgot to remove it before deploying", "html comments"], answer: "FLAG{inspect_the_source}" },
      { matches: ["synt{ebg13_vf_whfg_n_gbyy}"], answer: "FLAG{rot13_is_just_a_toll}" },
      { matches: ["89 50 4e 47 0d 0a 1a 0a", "89504e470d0a1a0a"], answer: "FLAG{png}" },
      { matches: ["ppyq{zgqilovc_sw_der}"], answer: "FLAG{vigenere_is_fun}" },
      { matches: ["select * from users where username", "force the query to evaluate to true"], answer: "' OR 1=1 --" },
      { matches: ["fl4g{n3v3r_g0nn4_g1v3_y0u_up}"], answer: "FLAG{never_gonna_give_you_up}" },
      { matches: ["extract human-readable text from a binary", "read-only data sections"], answer: "FLAG{strings}" },
      { matches: ["59 53 5e 58 64 67 70 6d 40 76 6c 40 79 6a 71 62", "59535e586467706d40766c40796a7162"], answer: "FLAG{xor_is_fun}" },
      { matches: ["flag%7bweb_urls_are_tricky%7d"], answer: "FLAG{web_urls_are_tricky}" },
      { matches: ["zmfsc2u="], answer: "dHJ1ZQ==" }
    ];

    for (const challenge of knownChallenges) {
      if (challenge.matches.some((value) => normalized.includes(value) || compact.includes(value.replace(/\s+/g, "")))) {
        return challenge.answer;
      }
    }
    return null;
  }
  function placeHiddenFlag() {
    const margin = 28;
    const headerSpace = 82;
    const maxX = Math.max(margin, window.innerWidth - hiddenFlagTrigger.offsetWidth - margin);
    const maxY = Math.max(headerSpace, window.innerHeight - hiddenFlagTrigger.offsetHeight - margin);
    const x = margin + Math.random() * Math.max(0, maxX - margin);
    const y = headerSpace + Math.random() * Math.max(0, maxY - headerSpace);
    hiddenFlagTrigger.style.left = `${Math.round(x)}px`;
    hiddenFlagTrigger.style.top = `${Math.round(y)}px`;
  }
  hiddenFlagTrigger.addEventListener("click", async () => {
    const input = elements.input.value.trim();
    if (!input) return;
    const verifiedAnswer = knownCtfAnswer(input);
    if (verifiedAnswer) {
      hiddenFlagValue.textContent = verifiedAnswer;
      hiddenFlagAnswer.hidden = false;
      return;
    }

    hiddenFlagValue.textContent = "Processing...";
    hiddenFlagAnswer.hidden = false;
    try {
      // Always decode for the hidden-answer reveal, even if the visible
      // workspace was accidentally left in Encode mode.
      const decoded = await transform(getOperation(), input, "decode", elements.keyInput.value);
      hiddenFlagValue.textContent = decoded || "No decoded result found.";
    } catch (error) {
      hiddenFlagValue.textContent = "Select the correct cipher operation and key, then try again.";
    }
  });
  closeHiddenFlag.addEventListener("click", () => {
    hiddenFlagAnswer.hidden = true;
    placeHiddenFlag();
    hiddenFlagTrigger.focus();
  });
  window.addEventListener("resize", () => {
    if (!hiddenFlagTrigger.hidden) placeHiddenFlag();
  });
  setInterval(() => {
    if (!hiddenFlagTrigger.hidden && hiddenFlagAnswer.hidden) {
      placeHiddenFlag();
    }
  }, 3000);

  document.addEventListener("keydown", (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === "Enter") {
      event.preventDefault();
      runTransform(true);
    }
    if (event.key === "Escape" && !elements.modal.hidden) closeModal(false);
  });

  refreshOptions(state.category, state.operationId);
  updateOperationUI(false);
  updateCounts();
  elements.input.focus();
})();
