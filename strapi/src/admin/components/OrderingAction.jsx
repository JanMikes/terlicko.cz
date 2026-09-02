import * as React from 'react';
import { useLocation } from 'react-router-dom';
import { useFetchClient, useNotification } from '@strapi/strapi/admin';
import { Box, Button, Flex, IconButton, Loader, Modal, Typography } from '@strapi/design-system';
import { ArrowDown, ArrowUp, Drag } from '@strapi/icons';

/**
 * Content types that can be reordered, and the integer field holding the order.
 * The frontend reads both: `StrapiContent::getTagyData()` sorts `tagies` by
 * `rank`, `StrapiContent::getMenuData()` sorts `menus` by `Poradi`.
 *
 * Add a content type here once it has an integer field to sort on.
 */
const ORDERABLE = {
  'api::tagy.tagy': { rankField: 'rank', labelField: 'Tag' },
  'api::menu.menu': { rankField: 'Poradi', labelField: 'Nadpis' },
};

// `Poradi` declares `min: 1`, so ranks are 1-based for every content type.
const FIRST_RANK = 1;
const PAGE_SIZE = 100;

const useCurrentModel = () => {
  const { pathname } = useLocation();

  return React.useMemo(() => {
    const match = pathname.match(/\/content-manager\/collection-types\/([^/?]+)/);

    return match ? decodeURIComponent(match[1]) : null;
  }, [pathname]);
};

const moveItem = (entries, from, to) => {
  if (from === to || to < 0 || to >= entries.length) {
    return entries;
  }

  const next = [...entries];
  next.splice(to, 0, ...next.splice(from, 1));

  return next;
};

const OrderingModal = ({ uid, config, onClose }) => {
  const { get, put } = useFetchClient();
  const { toggleNotification } = useNotification();

  const [entries, setEntries] = React.useState(null);
  const [draggedId, setDraggedId] = React.useState(null);
  const [isSaving, setIsSaving] = React.useState(false);

  React.useEffect(() => {
    let cancelled = false;

    get(`/content-manager/collection-types/${uid}?page=1&pageSize=${PAGE_SIZE}&sort=${config.rankField}:asc`)
      .then(({ data }) => {
        if (!cancelled) {
          setEntries(data.results ?? []);
        }
      })
      .catch(() => {
        if (!cancelled) {
          toggleNotification({ type: 'danger', message: 'Položky se nepodařilo načíst.' });
          onClose();
        }
      });

    return () => {
      cancelled = true;
    };
  }, [uid, config.rankField, get, toggleNotification, onClose]);

  const moveBy = (index, offset) => {
    setEntries((current) => moveItem(current, index, index + offset));
  };

  const moveOnto = (documentId, targetIndex) => {
    setEntries((current) =>
      moveItem(
        current,
        current.findIndex((entry) => entry.documentId === documentId),
        targetIndex
      )
    );
  };

  const save = async () => {
    setIsSaving(true);

    // Only write entries whose rank actually moved, so shuffling the last two
    // items does not rewrite every row.
    const changed = entries
      .map((entry, index) => ({ entry, rank: FIRST_RANK + index }))
      .filter(({ entry, rank }) => entry[config.rankField] !== rank);

    try {
      for (const { entry, rank } of changed) {
        await put(`/content-manager/collection-types/${uid}/${entry.documentId}`, {
          [config.rankField]: rank,
        });
      }

      toggleNotification({ type: 'success', message: 'Pořadí bylo uloženo.' });
      // The list view caches its query, so reload to show the new order.
      window.location.reload();
    } catch (error) {
      setIsSaving(false);
      toggleNotification({ type: 'danger', message: 'Pořadí se nepodařilo uložit.' });
    }
  };

  return (
    <Modal.Content>
      <Modal.Header>
        <Modal.Title>Upravit pořadí</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        {entries === null ? (
          <Flex justifyContent="center" padding={6}>
            <Loader small>Načítání…</Loader>
          </Flex>
        ) : (
          <Flex direction="column" alignItems="stretch" gap={1}>
            {entries.map((entry, index) => (
              <Box
                key={entry.documentId}
                draggable
                onDragStart={() => setDraggedId(entry.documentId)}
                onDragEnd={() => setDraggedId(null)}
                onDragOver={(event) => {
                  event.preventDefault();

                  if (draggedId && draggedId !== entry.documentId) {
                    moveOnto(draggedId, index);
                  }
                }}
                onDrop={(event) => event.preventDefault()}
                background={draggedId === entry.documentId ? 'primary100' : 'neutral0'}
                borderColor="neutral200"
                hasRadius
                paddingLeft={3}
                paddingRight={3}
                paddingTop={2}
                paddingBottom={2}
                style={{ cursor: 'grab' }}
              >
                <Flex gap={3}>
                  <Drag aria-hidden fill="neutral500" />
                  <Typography ellipsis>{entry[config.labelField] || entry.documentId}</Typography>
                  <Flex gap={1} marginLeft="auto">
                    <IconButton
                      label="Posunout nahoru"
                      variant="ghost"
                      disabled={index === 0}
                      onClick={() => moveBy(index, -1)}
                    >
                      <ArrowUp />
                    </IconButton>
                    <IconButton
                      label="Posunout dolů"
                      variant="ghost"
                      disabled={index === entries.length - 1}
                      onClick={() => moveBy(index, 1)}
                    >
                      <ArrowDown />
                    </IconButton>
                  </Flex>
                </Flex>
              </Box>
            ))}
          </Flex>
        )}
      </Modal.Body>
      <Modal.Footer>
        <Modal.Close>
          <Button variant="tertiary">Zrušit</Button>
        </Modal.Close>
        <Button onClick={save} loading={isSaving} disabled={entries === null}>
          Uložit pořadí
        </Button>
      </Modal.Footer>
    </Modal.Content>
  );
};

export const OrderingAction = () => {
  const uid = useCurrentModel();
  const [isOpen, setIsOpen] = React.useState(false);
  const config = uid ? ORDERABLE[uid] : undefined;

  if (!config) {
    return null;
  }

  return (
    <Modal.Root open={isOpen} onOpenChange={setIsOpen}>
      <Modal.Trigger>
        <Button variant="tertiary" startIcon={<Drag />}>
          Upravit pořadí
        </Button>
      </Modal.Trigger>
      {isOpen ? <OrderingModal uid={uid} config={config} onClose={() => setIsOpen(false)} /> : null}
    </Modal.Root>
  );
};
