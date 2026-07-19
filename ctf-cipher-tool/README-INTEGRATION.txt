CTF CIPHER LAB — INTEGRATION GUIDE

This folder is a complete standalone website. It does not require PHP,
a database, npm, or an internet connection.

FILES TO KEEP TOGETHER
- index.html
- styles.css
- app.js

OPTION 1 — OPEN AS ITS OWN PAGE
1. Copy the entire "ctf-cipher-tool" folder into your existing system.
2. Add a link to it from your sidebar or navigation:

   <a href="tools/ctf-cipher-tool/index.html">CTF Cipher Lab</a>

3. Adjust the path based on the folder location in your project.

OPTION 2 — DISPLAY INSIDE AN EXISTING PHP PAGE
Place this iframe where the tool should appear:

   <iframe
     src="tools/ctf-cipher-tool/index.html"
     title="CTF Cipher Lab"
     allow="clipboard-read; clipboard-write"
     style="width:100%;height:100vh;border:0;display:block;"
   ></iframe>

If your PHP page already has a fixed navbar, use a smaller height such as:

   style="width:100%;height:calc(100vh - 70px);border:0;display:block;"

NOTES
- All encoding and decoding runs in the visitor's browser.
- Input is not uploaded or saved.
- The Process Explanation panel shows the selected algorithm, key or shift,
  byte/letter transformations, and output format before the final answer.
- The tool works under XAMPP, standard PHP hosting, and static hosting.
- Keep the three required files in the same directory so their relative paths work.
- Use the tool only for CTFs, coursework, and data you are authorized to analyze.

SUPPORTED GROUPS
- Classic: Caesar, ROT13, ROT47, Atbash, Vigenere, Affine, Rail Fence, Bacon
- Encoding: Base64, Base32, Base58, Hex, Binary, Octal, decimal bytes, URL
- XOR and bytes: single-byte XOR, repeating-key XOR, RC4, XOR brute force
- Utilities: Morse, reverse, Unicode escapes, JWT inspection, SHA hashes,
  frequency analysis, and common-format auto detection
