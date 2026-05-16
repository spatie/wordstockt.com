# Rack rebalancing design

## Goal

Reduce, but not eliminate, the chance that a player's rack ends up with no consonants or no vowels. Genuine bad luck should still be possible.

## Trigger condition

A rack is considered "lopsided" when, evaluating the resulting full rack (tiles kept plus tiles just drawn):

1. The rack contains at least one tile, and
2. The rack has no blank wildcard, and
3. Every tile is a vowel, or every tile is a consonant.

Vowels are `A`, `E`, `I`, `O`, `U`. Every other letter is a consonant, including `Y` (it is treated as a consonant in both the Dutch and English distributions, and it is rare). If any blank tile (`*`) is present, the rack is never lopsided, because the player can always play the blank as the missing type.

## Mechanism

A single new method on `TileBag`:

```
drawForRack(keptTiles, count) -> drawnTiles
  drawn = draw(count)
  if not lopsided(keptTiles + drawn): return drawn
  returnTiles(drawn)        // existing method; also reshuffles the whole bag
  return draw(count)        // one retry, accept whatever comes out
```

At most one reshuffle and redraw happens per draw event. If the rack is still lopsided after the retry (for example, the bag is down to only vowels late in the game), the player keeps it. This preserves the possibility of bad luck by design.

`returnTiles` already appends the tiles and shuffles the entire bag, so it satisfies the "reshuffle the bag and pick letters again" requirement.

## Call sites

All three draw sites switch from `draw()` to `drawForRack()`:

* `CreateGameAction::addPlayer`. Opening rack, `keptTiles` is empty.
* `JoinGameAction`. Opening rack, `keptTiles` is empty.
* `PlayMoveAction::refillPlayerRack`. `keptTiles` is the leftover rack after the played tiles are removed.

## Ordering versus the existing blank lottery

The balance check runs before the existing `maybeGiveBlank()` step in each call site. That step only ever swaps a tile for a blank, which can only make a rack less lopsided, never more. Checking before it is therefore safe and conservative.

## Edge cases

All handled by the "retry once, then accept" rule:

* Empty draw or empty bag. No check is performed; behavior is unchanged.
* Draw count is capped at the bag size (existing `draw()` behavior).
* Near empty bag where rebalancing is impossible. The single retry runs and the result is accepted.

## Testing

Unit tests on `TileBag::drawForRack`:

* A rack that would be all vowels triggers a redraw.
* A rack that would be all consonants triggers a redraw.
* A blank present in the resulting rack skips the check (no redraw).
* A mixed rack (at least one vowel and one consonant) is left untouched.
* A deterministically still lopsided bag (a stub bag containing only vowels) returns a lopsided rack after exactly one retry, proving bad luck is still possible and only one retry happens.

Feature test:

* An opening rack drawn from a bag that has consonants available does not come back all vowels when a rebalance was possible.
